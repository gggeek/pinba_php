#!/usr/bin/env bash

## @todo fix:
# set env vars CONTAINER_USER_UID,CONTAINER_USER_GID before running docker or dc
# setup app on build, not on boot
# wait_for_bootstrap,
# cleanup vendors
# runtests
# resetdb
# simplify build options

# Manage the whole set of containers and run tests without having to learn Docker

# vars
BOOTSTRAP_OK_FILE=/var/run/bootstrap_ok
WEB_USER=docker
BOOTSTRAP_TIMEOUT=600
DOCKER_COMPOSE=docker-compose
INTERACTIVE=
PARALLEL_BUILD=
REBUILD=false
RECREATE=false
COVERAGE_OPTION=
SILENT=false
TTY=
VERBOSITY=
WEB_CONTAINER=

help() {
    printf "Usage: vm.sh [OPTIONS] COMMAND [OPTARGS]

Manages the Test Environment Docker Stack

Commands:
    build             build or rebuild the complete set of containers and set up eZ. Leaves the stack running
    cleanup WHAT      remove temporary data/logs/caches/etc... CATEGORY can be any of:
                        - containers      removes all the project's containers and their images
                        - dead-images     removes unused docker images. Can be quite beneficial to free up space
                        - docker-logs     NB: for this to work, you'll need to run this script as root, eg. with sudo -E
                        - logs            removes log files from the databases, webservers
    enter             run a shell in the test container
    exec \$cmd         execute a single shell command in the test container
    images [\$svc]     list container images
    kill [\$svc]       kill containers
    logs [\$svc]       view output from containers
    pause [\$svc]      pause the containers
    ps [\$svc]         show the status of running containers
    runtests [\$suite] execute the test suite using the test container (or a single test scenario eg. Tests/phpunit/05_TagsTest.php)
    services          list docker-compose services
    start [\$svc]      start the complete set of containers
    stop [\$svc]       stop the complete set of containers
    top [\$svc]        display the running container processes
    unpause [\$svc]    unpause the containers

Options:
    -h                print help
    -v                verbose mode

Advanced Options:
    -d                discard existing containers and force them to rebuild from scratch - when running 'build'
    -f                freshen: force app set up via resetting containers to clean-build status besides updating them if needed - when running 'build', 'start'
    -i                interactive - when running 'exec'
    -o PROVIDER       generate and upload code coverage data - when running 'runtests'. Providers: codecov, scrutinizer
    -t                allocate a pseudo-TTY - when running 'exec'
"
}

create_compose_command() {
    DOCKER_COMPOSE="${DOCKER_COMPOSE} -f docker-compose.yml -f docker-compose-${DB_TYPE}.yml"
    DOCKER_TESTSTACK_QUIET=${DOCKER_COMPOSE/ --verbose/}
}

build() {
    echo "[$(date)] Stopping running Containers..."

    ${DOCKER_COMPOSE} stop

    if [ ${REBUILD} = 'true' ]; then
        echo "[$(date)] Removing existing Containers..."

        ${DOCKER_COMPOSE} rm -f
    fi

    echo "[$(date)] Building Containers..."

    ${DOCKER_COMPOSE} build ${PARALLEL_BUILD} || exit $?

    echo "[$(date)] Starting Containers..."

    if [ ${RECREATE} = 'true' ]; then
        ${DOCKER_COMPOSE} up -d --force-recreate || exit $?
    else
        ${DOCKER_COMPOSE} up -d || exit $?
    fi

    wait_for_bootstrap all
    RETCODE=$?

    if [ $RETCODE -eq 0 ]; then
        echo "[$(date)] Build finished"
    else
        echo "[$(date)] Build finished. Exit code: ${RETCODE}"
    fi

    exit ${RETCODE}
}

check_requirements() {
    which docker >/dev/null 2>&1
    if [ $? -ne 0 ]; then
        printf "\n\e[31mPlease install docker & add it to \$PATH\e[0m\n\n" >&2
        exit 1
    fi

    which docker-compose >/dev/null 2>&1
    if [ $? -ne 0 ]; then
        printf "\n\e[31mPlease install docker-compose & add it to \$PATH\e[0m\n\n" >&2
        exit 1
    fi
}

# @todo loop over all args instead of allowing just one
cleanup() {
    case "${1}" in
        containers)
            if [ ${SILENT} != true ]; then
                echo "Do you really want to delete all project containers and their images?"
                select yn in "Yes" "No"; do
                    case $yn in
                        Yes ) break ;;
                        No ) exit 1 ;;
                    esac
                done
            fi

            ${DOCKER_COMPOSE} down --rmi all
        ;;
        docker-images | dead-images)
            cleanup_dead_docker_images
        ;;
        docker-logs)
            for CONTAINER in $(${DOCKER_TESTSTACK_QUIET} ps -q)
            do
                LOGFILE=$(docker inspect --format='{{.LogPath}}' ${CONTAINER})
                if [ -n "${LOGFILE}" ]; then
                    echo "" > ${LOGFILE}
                fi
            done
        ;;
        vendors)
            # @todo it would be better to do the removal from outside the container, but we would have to be sure of the
            #       location of the test stack compared to the project's root
            docker exec "${WEB_CONTAINER}" su "${WEB_USER}" -c "../teststack/bin/cleanup.sh vendors"
        ;;
        *)
            printf "\n\e[31mERROR:\e[0m unknown cleanup target ${1}\n\n" >&2
            help
            exit 1
        ;;
    esac
}

cleanup_dead_docker_images() {
    echo "[$(date)] Removing unused Docker images from disk..."
    DEAD_IMAGES=$(docker images | grep "<none>" | awk "{print \$3}")
    if [ -n "${DEAD_IMAGES}" ]; then
        docker rmi ${DEAD_IMAGES}
    fi
}

# Wait until containers have fully booted
wait_for_bootstrap() {
##
return 0

    if [ ${BOOTSTRAP_TIMEOUT} -le 0 ]; then
        return 0
    fi

    case "${1}" in
        all)
            # q: check all services or only the running ones?
            #BOOTSTRAP_CONTAINERS=$(${DOCKER_TESTSTACK_QUIET} config --services)
            BOOTSTRAP_CONTAINERS=$(${DOCKER_TESTSTACK_QUIET} ps --services | tr '\n' ' ')
        ;;
        app)
            BOOTSTRAP_CONTAINERS='ez'
        ;;
        *)
            #printf "\n\e[31mERROR:\e[0m unknown booting container: '${1}'\n\n" >&2
            #help
            #exit 1
            # @todo add check that this service is actually defined
            BOOTSTRAP_CONTAINERS=${1}
        ;;
    esac

    echo "[$(date)] Waiting for containers bootstrap to finish: ${BOOTSTRAP_CONTAINERS}..."

     START_TIME=$SECONDS
     ELAPSED=0
     i=0
     while [ $ELAPSED -le "${BOOTSTRAP_TIMEOUT}" ]; do
        sleep 1
        BOOTSTRAP_OK=''
        for BS_CONTAINER in ${BOOTSTRAP_CONTAINERS}; do
            printf "Waiting for ${BS_CONTAINER} ... "
            # @todo fix this check for the case of container not running / tty issues / etc...
            #       Eg. use instead a check such as `bash -c 'ps auxwww | fgrep "tail -f /dev/null" | fgrep -v grep'` ?
            OUT=$(${DOCKER_TESTSTACK_QUIET} exec -T ${BS_CONTAINER} cat ${BOOTSTRAP_OK_FILE} 2>&1)
            RETCODE=$?
            if [ ${RETCODE} -eq 0 ]; then
                printf "\e[32mdone\e[0m\n"
                BOOTSTRAP_OK="${BOOTSTRAP_OK} ${BS_CONTAINER}"
            else
                echo
                # to debug:
                #echo $OUT;
            fi
        done
        if [ -n "${BOOTSTRAP_OK}" ]; then
            for BS_CONTAINER in ${BOOTSTRAP_OK}; do
                BOOTSTRAP_CONTAINERS=${BOOTSTRAP_CONTAINERS//${BS_CONTAINER}/}
            done
            if [ -z  "${BOOTSTRAP_CONTAINERS// /}" ]; then
                break
            fi
        fi
        i=$(( i + 1 ))
        ELAPSED=$(( SECONDS - START_TIME ))
    done
    if [ $i -gt 0 ]; then echo; fi

    if [ -n "${BOOTSTRAP_CONTAINERS// /}" ]; then
        printf "\n\e[31mBootstrap process did not finish within ${BOOTSTRAP_TIMEOUT} seconds\e[0m\n\n" >&2
        # @todo we could show the docker logs
        return 1
    fi

    return 0
}

# @todo move to a function
# @todo allow parsing of cli options after args -- see fe. https://medium.com/@Drew_Stokes/bash-argument-parsing-54f3b81a6a8f
while getopts ":dfhio:tvy" opt
do
    case $opt in
        d)
            REBUILD=true
        ;;
        f)
            RECREATE=true
        ;;
        h)
            help
            exit 0
        ;;
        i)
            INTERACTIVE='-i'
        ;;
        o)
            COVERAGE_OPTION="-u ${OPTARG}"
        ;;
        t)
            TTY='-t'
        ;;
        v)
            VERBOSITY=-v
            DOCKER_COMPOSE="${DOCKER_COMPOSE} --verbose"
        ;;
        y)
            SILENT=true
        ;;
        \?)
            printf "\n\e[31mERROR:\e[0m unknown option '-${OPTARG}'\n\n" >&2
            help
            exit 1
        ;;
    esac
done
shift $((OPTIND-1))

COMMAND=$1

check_requirements

cd "$(dirname -- "${BASH_SOURCE[0]}"})"

WEB_CONTAINER=${COMPOSE_PROJECT_NAME:-pinbapolyfill}_php

case "${COMMAND}" in
    build)
        build
    ;;

    cleanup)
        # @todo allow to pass in many cleanup targets in one go
        cleanup "${2}"
    ;;

    config)
        ${DOCKER_COMPOSE} config ${2}
    ;;


    # courtesy command alias - same as 'ps'
    containers)
        ${DOCKER_COMPOSE} ps ${2}
    ;;

    enter | shell | cli)
        docker exec -ti "${WEB_CONTAINER}" su "${WEB_USER}"
    ;;

    exec)
        # scary line ? found it at https://stackoverflow.com/questions/12343227/escaping-bash-function-arguments-for-use-by-su-c
        shift
        docker exec $INTERACTIVE $TTY "${WEB_CONTAINER}" su "${WEB_USER}" -c '"$0" "$@"' -- exec "$@"
    ;;

    images)
        ${DOCKER_COMPOSE} images ${2}
    ;;

    kill)
        ${DOCKER_COMPOSE} kill ${2}
    ;;

    logs)
        ${DOCKER_COMPOSE} logs ${2}
    ;;

    pause)
        ${DOCKER_COMPOSE} pause ${2}
    ;;

    ps)
        ${DOCKER_COMPOSE} ps ${2}
    ;;

    resetdb)
        # @todo allow this to be run from within the test container too
        # q: do we need -ti ?
        ##docker exec "${WEB_CONTAINER}" su "${WEB_USER}" -c "../teststack/bin/create-db.sh"
    ;;

    runtests)
        shift
        # q: do we need -ti ?
        docker exec "${WEB_CONTAINER}" su "${WEB_USER}" -c '"$0" "$@"' -- ../teststack/bin/runtests.sh ${COVERAGE_OPTION} ${VERBOSITY} "$@"
    ;;

    services)
        ${DOCKER_COMPOSE} config --services | sort
    ;;

    start)
        if [ ${RECREATE} = 'true' ]; then
            ${DOCKER_COMPOSE} up -d --force-recreate || exit $?
        else
            ${DOCKER_COMPOSE} up -d ${2} || exit $?
        fi

        if [ -z "${2}" ]; then
            wait_for_bootstrap all
            exit $?
        else
            wait_for_bootstrap ${2}
            exit $?
        fi
    ;;

    stop)
        ${DOCKER_COMPOSE} stop ${2}
    ;;

    top)
        ${DOCKER_COMPOSE} top ${2}
    ;;

    unpause)
        ${DOCKER_COMPOSE} unpause ${2}
    ;;

    *)
        printf "\n\e[31mERROR:\e[0m unknown command '${COMMAND}'\n\n" >&2
        help
        exit 1
    ;;
esac

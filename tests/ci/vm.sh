#!/usr/bin/env bash

# @todo runtests -o should generate html coverage, not xml
# @todo reimplement resetdb
# @todo simplify/verify cli options

# Manage the whole set of containers and run tests without having to learn Docker

# vars
WEB_USER=docker
DOCKER_COMPOSE=docker-compose
INTERACTIVE=
PARALLEL_BUILD=
REBUILD=false
RECREATE=false
COVERAGE_OPTION=
SILENT=false
TTY=
VERBOSITY=
WEB_CONTAINER=${COMPOSE_PROJECT_NAME:-pinbapolyfill}-php

help() {
    printf "Usage: vm.sh [OPTIONS] COMMAND [OPTARGS]

Manages the Test Environment Docker Stack

Commands:
    build             build or rebuild the complete set of containers and set up eZ. Leaves the stack running
    cleanup WHAT      remove temporary data/logs/caches/etc... CATEGORY can be any of:
                        - containers      removes all the project's containers and their images
                        - dead-images     removes unused docker images. Can be quite beneficial to free up space
                        - docker-logs     NB: for this to work, you'll need to run this script as root, eg. with sudo -E
                        - vendors         removes composers vendors and locks file
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

Env vars: TESTSTACK_UBUNTU_VERSION (focal), TESTSTACK_PHP_VERSION (default)
"
}

create_compose_command() {
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
        ${DOCKER_COMPOSE} up -d --force-recreate
    else
        ${DOCKER_COMPOSE} up -d
    fi
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
            # we are executing in the project's root, thanks to a cd call at the start
            if [ -f composer.lock ]; then rm composer.lock; fi
            if [ -d vendors ]; then rm -rf vendors; fi
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

load_config() {
    export CONTAINER_USER_UID="$(id -u)"
    export CONTAINER_USER_GID="$(id -g)"
    create_compose_command
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
            COVERAGE_OPTION="--coverage-clover=coverage.clover"
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

load_config

cd "$(dirname -- "${BASH_SOURCE[0]}"})"

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
        docker exec "${WEB_CONTAINER}" su "${WEB_USER}" -c '"$0" "$@"' -- ./vendor/bin/phpunit ${COVERAGE_OPTION} ${VERBOSITY} tests
    ;;

    services)
        ${DOCKER_COMPOSE} config --services | sort
    ;;

    start)
        if [ ${RECREATE} = 'true' ]; then
            ${DOCKER_COMPOSE} up -d --force-recreate
        else
            ${DOCKER_COMPOSE} up -d ${2}
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

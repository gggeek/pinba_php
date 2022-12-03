ARG ubuntu_version=focal

FROM ubuntu:${ubuntu_version}

ARG php_version=default

# Copy all the required shell scripts
COPY setup/*.sh /root/build/
COPY config/* /root/config/

RUN mkdir -p /usr/share/man/man1; mkdir -p /usr/share/man/man7; \
    apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y upgrade && \
    cd /root/build/ && \
    chmod 755 *.sh && \
    ./install_packages.sh && \
    ./create_user.sh && \
    ./setup_apache.sh && \
    ./setup_php.sh "${php_version}" && \
    ./setup_composer.sh && \
    cd /var/www/html && \
    # no need for pinboard (yet)
    #/root/build/setup_pinboard.sh && \
    /root/build/setup_adminer.sh

COPY entrypoint.sh /root/
RUN chmod 755 /root/entrypoint*.sh

# @todo can we avoid hardcoding this here? We can f.e. get it passed down as ARG...
WORKDIR /home/docker/build

EXPOSE 80

ENTRYPOINT ["/root/entrypoint.sh"]

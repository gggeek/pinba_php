FROM tony2001/pinba

# @todo test: can't we just alter mysql config to allow network access with `root` account?

# Copy all the required config files
COPY setup/* /root/build/

RUN apt-get update && \
    #DEBIAN_FRONTEND=noninteractive apt-get -y upgrade && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-client

COPY entrypoint.sh /root/
RUN chmod 755 /root/entrypoint*.sh

ENTRYPOINT ["/root/entrypoint.sh"]
CMD ["mysqld"]

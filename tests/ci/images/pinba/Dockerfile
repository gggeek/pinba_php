FROM tony2001/pinba

# @todo test

RUN apt-get update && \
    #DEBIAN_FRONTEND=noninteractive apt-get -y upgrade && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-client

COPY entrypoint.sh /root/
RUN chmod 755 /root/entrypoint*.sh

ENTRYPOINT ["/root/entrypoint.sh"]
CMD ["mysqld"]

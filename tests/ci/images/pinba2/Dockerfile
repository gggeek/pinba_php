FROM anchorfree/pinba2

# source of the base image: https://github.com/badoo/pinba2/blob/master/Dockerfile

# Copy all the required config files
COPY setup/* /root/build/

RUN cd /root/build/ && \
    chmod 755 *.sh && \
    ./setup_mysql.sh

COPY entrypoint.sh /root/
RUN chmod 755 /root/entrypoint*.sh

ENTRYPOINT ["/root/entrypoint.sh"]
#CMD ["mysqld"]

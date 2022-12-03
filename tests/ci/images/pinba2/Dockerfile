FROM anchorfree/pinba2

# source of the base image: https://github.com/badoo/pinba2/blob/master/Dockerfile

COPY entrypoint.sh /root/
RUN chmod 755 /root/entrypoint*.sh

ENTRYPOINT ["/root/entrypoint.sh"]
CMD ["mysqld"]

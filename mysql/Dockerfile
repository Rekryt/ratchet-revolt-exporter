FROM bitnami/mariadb:latest

ADD initdb.d/* /docker-entrypoint-initdb.d
ADD conf.d/my.cnf /opt/bitnami/mariadb/conf/my_custom.cnf

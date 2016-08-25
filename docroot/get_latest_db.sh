#!/usr/bin/env sh
# See PROD_TO_IMAGE builder
docker network create --subnet=172.18.0.0/16 buildnet || true
docker rm -f DBPROD || true
docker run --net buildnet --ip 172.18.254.254 --name DBPROD -di cibox/mysql || true
docker exec DBPROD service mysql start

mysql -uroot -proot -h 172.18.254.254 -e "drop database if exists drupal; create database if not exists drupal;"
mysql -uroot -proot -h 172.18.254.254 -e "drop database if exists drupal_openy; create database if not exists drupal_openy;"
mysql -uroot -proot -h 172.18.254.254 -e "drop database if exists drupal_redwing; create database if not exists drupal_redwing;"

wget --output-document=/var/www/backup/latest_prod.sql.gz http://propeople:givemebackup@178.62.224.139/backup/latest_prod.sql.gz
wget --output-document=/var/www/backup/latest_redwing_prod.sql.gz http://propeople:givemebackup@178.62.224.139/backup/latest_redwing_prod.sql.gz
wget --output-document=/var/www/backup/latest_openy_prod.sql.gz http://propeople:givemebackup@178.62.224.139/backup/latest_openy_prod.sql.gz

zcat /var/www/backup/latest_prod.sql.gz | mysql -uroot -proot -h 172.18.254.254 drupal
zcat /var/www/backup/latest_redwing_prod.sql.gz | mysql -uroot -proot -h 172.18.254.254 drupal_redwing
zcat /var/www/backup/latest_openy_prod.sql.gz | mysql -uroot -proot -h 172.18.254.254 drupal_openy

# Make DB smaller.
mysql -uroot -proot -h 172.18.254.254 -e "use drupal; show tables;" -s | egrep "^cache_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal; show tables;" -s | egrep "^field_deleted_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal; show tables;" -s | egrep "^groupex_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal; show tables;" -s | egrep "^key_value_expire" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal; show tables;" -s | egrep "^migrate_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal; show tables;" -s | egrep "^mindbody_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal; show tables;" -s | egrep "^queue" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal; show tables;" -s | egrep "^search_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal;TRUNCATE TABLE @@"

mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing; show tables;" -s | egrep "^cache_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing; show tables;" -s | egrep "^field_deleted_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing; show tables;" -s | egrep "^groupex_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing; show tables;" -s | egrep "^key_value_expire" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing; show tables;" -s | egrep "^migrate_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing; show tables;" -s | egrep "^mindbody_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing; show tables;" -s | egrep "^queue" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing;TRUNCATE TABLE @@"
mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing; show tables;" -s | egrep "^search_" | xargs -I "@@" mysql -uroot -proot -h 172.18.254.254 -e "use drupal_redwing;TRUNCATE TABLE @@"


docker rmi -f dbprod || true
date=`date`; docker commit -m "$date" DBPROD dbprod
docker rm -f DBPROD || true
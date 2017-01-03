FROM php:5.6-apache

ENV APACHE_CI_ENV production

COPY websystem/system /var/www/html/system
COPY websystem/index.php /var/www/html/
COPY resources /var/www/html/resources
COPY application /var/www/html/application

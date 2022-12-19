FROM ubuntu:latest

ENV DEBIAN_FRONTEND noninteractive
ENV DEBCONF_NONINTERACTIVE_SEEN true

RUN apt-get update && apt-get upgrade
RUN apt-get install -y bash
RUN apt-get install -y nginx
RUN apt-get install -y php8.1 php8.1-fpm php8.1-opcache
RUN apt-get install -y php8.1-gd php8.1-curl
RUN apt-get install -y php8.1-pgsql
RUN apt-get install -y php-cli unzip less
RUN apt-get install curl
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
RUN php -r "if (hash_file('SHA384', '/tmp/composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer


COPY server/etc/nginx /etc/nginx
COPY server/etc/php /etc/php8.1
COPY src /usr/share/nginx/html
EXPOSE 80
EXPOSE 443

RUN cd /usr/share/nginx/html && composer update

STOPSIGNAL SIGTERM

CMD ["/bin/bash", "-c", "php-fpm8.1 && chmod 777 /var/run/php/php8.1-fpm.sock && chmod 755 /usr/share/nginx/html/* && nginx -g 'daemon off;'"]
FROM php:8.3.12-apache-bullseye


RUN apt-get update && \ 
    apt-get upgrade -y && \
    apt-get install -y freetype2-demos libfreetype6 libfreetype6-dev zlib1g-dev libpq-dev libicu-dev libxml2-dev libcurl4-openssl-dev \
    pkg-config libssl-dev libzip-dev zlib1g-dev \
    libjpeg62-turbo-dev libpng-dev git && \ 
    apt-get install -y vim && \
    apt-get install -y wget && \
    apt-get install -y libpng-dev && \
    apt-get install -y zip && \
    apt-get install -y unzip && \
    apt-get install -y libzip-dev && \
    apt-get install -y tar && \
    apt-get install -y openssl && \
    apt-get install -y libcurl4 && \
    apt-get install -y sendmail-bin && \
    apt-get install -y python3 libxrender1 && \
    apt-get install -y python3-pip && \
    apt-get install -y libpng-dev libzip-dev libgd3 libgd-dev && \
    python3 -m pip install --upgrade pip && \
    python3 -m pip install rdkit pillow
    # \    
    # next line for email testing     
    #  && apt-get install -y golang-go 


RUN apt-get update; \
    apt-get install -y libpq5 libpq-dev; \
    docker-php-ext-configure gd --enable-gd --prefix=/usr --with-jpeg --with-freetype; \
    docker-php-ext-install pdo pdo_pgsql gd zip bcmath bz2 calendar ctype curl dba dl_test dom enchant exif ffi fileinfo filter ftp gd gettext gmp hash iconv imap intl json ldap mbstring mysqli oci8 odbc opcache pcntl pdo pdo_pgsql pgsql phar posix pspell random readline reflection session shmop simplexml snmp soap sockets sodium spl standard sysvmsg sysvsem sysvshm tidy tokenizer xml xmlreader xmlwriter xsl zend_test; \    
    docker-php-ext-enable pdo pdo_pgsql gd zip bcmath bz2 calendar ctype curl dba dl_test dom enchant exif ffi fileinfo filter ftp gd gettext gmp hash iconv imap intl json ldap mbstring mysqli oci8 odbc opcache pcntl pdo pdo_pgsql pgsql phar posix pspell random readline reflection session shmop simplexml snmp soap sockets sodium spl standard sysvmsg sysvsem sysvshm tidy tokenizer xml xmlreader xmlwriter xsl zend_test; \
    apt-get autoremove --purge -y libpq-dev; \
    apt-get clean ; \    
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*


# --- install mailhog's mhsendmail
# RUN export GOPATH="/usr/local/go" && \
#     export PATH=$PATH:$GOPATH/bin && \
#     go get github.com/mailhog/mhsendmail && \
#     go install github.com/mailhog/mhsendmail
#   RUN echo "sendmail_path=/usr/local/go/bin/mhsendmail" >> $PHP_INI_DIR/php.ini



COPY ./WEBSITE /var/www/html
COPY ./BACKEND/SCRIPT/API/queries.php /var/www/html/module/api/api_home/api_queries.php

# Install packages
COPY --from=docker.io/library/composer:2.8.1 /usr/bin/composer /usr/local/bin/composer

ENV  COMPOSER_ALLOW_SUPERUSER=1

## Production packages
RUN composer require phpmailer/phpmailer
RUN composer config vendor-dir /usr/local/bin/website-vendor/
## Dev packages
# RUN composer require --dev phpunit/phpunit ^9

RUN composer install

RUN a2enmod rewrite

RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf


#---------------------------------------------#
# attach to a running container in vscode 
# using remote development extension pack + remote explorer extension

# RUN mkdir -p /var/www/.vscode-server
# RUN chown www-data /var/www/.vscode-server

#---------------------------------------------#

# --- install xdebug after composer runs

# RUN pear config-set http_proxy ${http_proxy} && \
#     pear config-set php_ini $PHP_INI_DIR/php.ini && \
#     pecl config-set php_ini "${PHP_INI_DIR}/php.ini" && \
#     #  only comment in this line if running mailhog tests
    # echo "sendmail_path=/usr/local/go/bin/mhsendmail" >> $PHP_INI_DIR/php.ini && \
#     pecl install xdebug

# COPY ./WEBSITE/99-xdebug.ini "${PHP_INI_DIR}/conf.d"
#---------------------------------------------#

# Set the max file size to 60mb
RUN echo "upload_max_filesize = 60M" > /usr/local/etc/php/conf.d/max-file-size.ini
RUN echo "post_max_size = 30M" >> /usr/local/etc/php/conf.d/max-file-size.ini
RUN echo "memory_limit = 2000M" >>  /usr/local/etc/php/conf.d/max-file-size.ini
RUN echo "max_execution_time = 120" >>  /usr/local/etc/php/conf.d/max-file-size.ini

# Set user to be able to write to mounted volume
# USER www-data

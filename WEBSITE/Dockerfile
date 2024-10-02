FROM php:8.3.8-apache-bullseye


RUN apt-get update && \ 
    apt-get upgrade -y && \
    apt-get install -y git && \ 
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
    python3 -m pip install --upgrade pip && \
    python3 -m pip install rdkit pillow
    # \    
    # next line for email testing     
    #  && apt-get install -y golang-go 


RUN apt-get update; \
    apt-get install -y libpq5 libpq-dev; \
    docker-php-ext-install pdo pdo_pgsql gd zip; \    
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

# Install packages
COPY --from=docker.io/library/composer:2.6.3 /usr/bin/composer /usr/local/bin/composer

ENV  COMPOSER_ALLOW_SUPERUSER=1

## Production packages
RUN composer require phpoffice/phpspreadsheet --with-all-dependencies
RUN composer require phpoffice/phppresentation --with-all-dependencies
RUN composer require phpmailer/phpmailer
RUN composer require aws/aws-sdk-php --with-all-dependencies
RUN composer config vendor-dir /usr/local/bin/website-vendor/
RUN composer require microsoft/microsoft-graph
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

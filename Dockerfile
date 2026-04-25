FROM php:8.3-apache

# Modules Apache + extensions PHP pour MariaDB, images et EXIF
# Apache modules + PHP extensions for MariaDB, images and EXIF
RUN a2enmod rewrite headers expires && \
    docker-php-ext-install pdo pdo_mysql && \
    apt-get update && apt-get install -y libexif-dev && \
    docker-php-ext-install exif && \
    rm -rf /var/lib/apt/lists/*

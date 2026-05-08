FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

# 🔥 أهم تعديل: Apache يشتغل على PORT الديناميكي
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80

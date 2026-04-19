FROM php:8.2-fpm

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql

# Opcache para mejor performance en producción
RUN docker-php-ext-install opcache

# Instalar utilidades opcionales
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copiar el código dentro de la imagen (a diferencia del dev que lo monta)
COPY ./app /var/www/html

# Permisos correctos
RUN chown -R www-data:www-data /var/www/html
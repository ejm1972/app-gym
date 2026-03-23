FROM php:8.2-apache

# Instalar extensión PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite de Apache (por si se necesita en el futuro)
RUN a2enmod rewrite

# Configurar AllowOverride para .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

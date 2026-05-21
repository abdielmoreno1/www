# Usa una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instala extensiones necesarias (para MySQL/PostgreSQL)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql

# Habilita mod_rewrite de Apache para URLs amigables
RUN a2enmod rewrite

# Configura Apache para que use index.php como página principal
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Copia todos tus archivos al contenedor
COPY . /var/www/html/

# Da permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expone el puerto 80
EXPOSE 80

# Inicia Apache
CMD ["apache2-foreground"]
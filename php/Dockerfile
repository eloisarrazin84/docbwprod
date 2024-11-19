FROM php:8.1-apache

# Installez les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Activez mod_rewrite pour Apache
RUN a2enmod rewrite

# Copiez les fichiers dans le conteneur
COPY . /var/www/html

# Définissez le répertoire de travail
WORKDIR /var/www/html

# Assurez-vous que les permissions sont correctes
RUN chown -R www-data:www-data /var/www/html

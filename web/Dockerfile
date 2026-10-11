FROM php:8.2-apache

# Install PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Enable required Apache modules
RUN a2enmod rewrite headers

# Security hardening: Hide PHP and Apache version banners
RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini \
    && echo "ServerTokens Prod" > /etc/apache2/conf-available/security.conf \
    && echo "ServerSignature Off" >> /etc/apache2/conf-available/security.conf \
    && a2enconf security

# Copy application source code
COPY src/ /var/www/html/

# Secure file permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
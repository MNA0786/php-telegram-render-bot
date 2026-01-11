FROM php:8.2-apache
RUN a2enmod rewrite
RUN docker-php-ext-install mysqli
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf
WORKDIR /var/www/html
COPY . /var/www/html/
RUN chmod 777 users.json error.log
EXPOSE 10000
CMD ["apache2-foreground"]

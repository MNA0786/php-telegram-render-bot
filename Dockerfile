# Use official PHP + Apache image
FROM php:8.2-apache

# Increase memory for 4GB+ uploads
RUN echo "memory_limit=1024M" >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini

# Enable file uploads
RUN echo "upload_max_filesize = 5G" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size = 5G" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "max_execution_time = 0" >> /usr/local/etc/php/conf.d/uploads.ini

# Change Apache port (optional, Render friendly)
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf

# Workdir
WORKDIR /var/www/html

# Copy bot files
COPY . /var/www/html/

# Set permissions for storage files
RUN chmod 777 users.json error.log

# Expose port 10000 for Render
EXPOSE 10000

# Run Apache in foreground
CMD ["apache2-foreground"]

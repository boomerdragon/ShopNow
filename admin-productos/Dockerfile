FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Enable Apache modules for URL rewriting
RUN a2enmod rewrite

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]

FROM php:8.1-cli

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Expose the Railway port
EXPOSE ${PORT}

# Run the PHP built-in server on the port provided by Railway
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} router.php"]

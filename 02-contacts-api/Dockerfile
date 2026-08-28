FROM php:8.2-cli-alpine

# sqlite-dev fornece os headers que o pdo_sqlite precisa pra compilar
RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite

WORKDIR /app

# Install Composer itself (not vendor packages - this project has zero
# runtime dependencies by design, see README.md).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

# Generates vendor/autoload.php for our own PSR-4 classes. No network call:
# this project has zero required runtime packages, see README.md.
RUN composer dump-autoload --optimize --no-interaction

# Only used if DB_CONNECTION=sqlite (docker-compose.yml uses mysql by default).
RUN mkdir -p database

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]

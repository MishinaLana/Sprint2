FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    librdkafka-dev

RUN pecl install rdkafka \
    && docker-php-ext-enable rdkafka

WORKDIR /app

COPY ./index.php /app

EXPOSE 8082

CMD ["php", "-S", "0.0.0.0:8082", "-t", "/app"]

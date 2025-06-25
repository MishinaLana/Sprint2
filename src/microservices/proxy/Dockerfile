FROM php:8.3-fpm-alpine

WORKDIR /app

COPY ./index.php /app

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "/app"]

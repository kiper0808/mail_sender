FROM php:8.2-cli

# Устанавливаем cron, необходимые пакеты и зависимости для Composer
RUN apt-get update && apt-get install -y \
    cron \
    supervisor \
    mariadb-client \
    curl \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем драйверы для MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# Устанавливаем рабочую директорию
WORKDIR /var/www/html

# Копируем только composer.json и composer.lock (если есть), чтобы использовать кеш Docker
COPY composer.json /var/www/html/

# Устанавливаем зависимости через Composer
RUN composer install -v --no-dev --optimize-autoloader

# Устанавливаем права на папку vendor
RUN chmod -R 775 /var/www/html/vendor

# Копируем исходники проекта
COPY src /var/www/html/
COPY vendor /var/www/html/vendor

# Копируем кастомные настройки PHP
COPY config/php.ini /usr/local/etc/php/conf.d/

# Копируем crontab в правильное место
COPY config/crontab /etc/cron.d/custom_cron
RUN chmod 0644 /etc/cron.d/custom_cron

# Добавляем задания в cron и запускаем его
RUN crontab /etc/cron.d/custom_cron

# Запускаем cron и держим контейнер активным
CMD ["sh", "-c", "cron && tail -f /dev/null"]

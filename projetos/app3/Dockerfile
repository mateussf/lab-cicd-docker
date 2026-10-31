# Imagem de PRODUÇÃO. O desenvolvimento usa o Sail (compose.yaml); a entrega usa este arquivo.

# Estágio comum: PHP 8.5 + Apache apontando para public/
FROM php:8.5.12-apache AS base
RUN apt-get update \
 && apt-get install -y --no-install-recommends unzip \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-install pdo_mysql \
 && a2enmod rewrite \
 && sed -ri 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# Testes na MESMA base da produção, com as dependências de dev; se falharem, o build falha
FROM base AS testes
COPY . .
RUN composer install --no-interaction --prefer-dist --no-progress
RUN cp .env.example .env && php artisan key:generate && php artisan test

# Imagem entregue: sem dependências de dev; a configuração (APP_KEY, banco) vem do ambiente
FROM base AS final
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && chown -R www-data:www-data storage bootstrap/cache

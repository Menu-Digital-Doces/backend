# Use uma imagem base PHP com FPM (FastCGI Process Manager)
# Alpine é uma distribuição Linux leve, ideal para imagens Docker menores
FROM php:8.2-fpm-alpine

# Instale as dependências do sistema operacional necessárias
# Nginx para servir a aplicação, git para possíveis dependências,
# e outras libs para extensões PHP como gd, pdo_mysql, zip, intl, etc.
RUN apk add --no-cache \
    nginx \
    git \
    build-base \
    libpng-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    freetype-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) pdo_mysql opcache bcmath zip gd intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && rm -rf /var/cache/apk/*

# Instale o Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Defina o diretório de trabalho dentro do contêiner
WORKDIR /app

# Copie os arquivos da sua aplicação Laravel para o diretório de trabalho
# O .dockerignore garante que arquivos desnecessários não sejam copiados
COPY . .

# Instale as dependências do Composer
# --no-dev: não instala dependências de desenvolvimento
# --optimize-autoloader: otimiza o autoloader para produção
# --no-scripts: evita a execução de scripts do Composer durante a build (pode ser problemático em alguns ambientes)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Crie um arquivo .env básico se não existir (para que artisan funcione durante a build)
# Isso é um fallback; as variáveis de ambiente reais virão do Cloud Run
RUN cp .env.example .env || true

# Gere a chave da aplicação Laravel
# Esta chave será sobrescrita pelas variáveis de ambiente do Cloud Run em produção
RUN php artisan key:generate

# Execute as otimizações do Laravel para produção
# RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Defina as permissões corretas para os diretórios de storage e cache do Laravel
# O usuário www-data é o usuário padrão do Nginx/PHP-FPM em muitas distribuições
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Copie a configuração do Nginx que criamos no Passo 1
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Crie um socket para o PHP-FPM
RUN mkdir -p /var/run/php-fpm \
    && chown www-data:www-data /var/run/php-fpm

# Exponha a porta 8080, que é a porta que o Cloud Run espera
EXPOSE 8080

# Comando para iniciar o PHP-FPM e o Nginx
# O PHP-FPM é iniciado em segundo plano e o Nginx em primeiro plano
CMD php-fpm --daemonize && nginx -g 'daemon off;'

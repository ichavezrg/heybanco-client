FROM ubuntu:22.04

# Definir variables de entorno
ENV TZ=America/Mexico_city
ENV PHP_VR=8.4
ARG DEBIAN_FRONTEND=noninteractive

# Configurar zona horaria
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Instalar dependencias y limpiar caché
RUN apt-get update && apt-get install -y software-properties-common \
    && apt-add-repository ppa:ondrej/php -y \
    && apt-get update \
    && apt-get install -y php${PHP_VR} zip unzip curl nano openssl \
       php${PHP_VR}-mysql php${PHP_VR}-gmp php${PHP_VR}-zip php${PHP_VR}-curl \
       php${PHP_VR}-gd php${PHP_VR}-bcmath php${PHP_VR}-mbstring php${PHP_VR}-xml \
       php${PHP_VR}-soap php${PHP_VR}-imagick php${PHP_VR}-mongodb php${PHP_VR}-redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Definir directorio de trabajo
WORKDIR /app

# Copiar archivos de la aplicación
COPY ./ ./

# Agregar configuración de legacy provider a OpenSSL existente
RUN echo "" >> /etc/ssl/openssl.cnf && \
    echo "[provider_sect]" >> /etc/ssl/openssl.cnf && \
    echo "default = default_sect" >> /etc/ssl/openssl.cnf && \
    echo "legacy = legacy_sect" >> /etc/ssl/openssl.cnf && \
    echo "" >> /etc/ssl/openssl.cnf && \
    echo "[legacy_sect]" >> /etc/ssl/openssl.cnf && \
    echo "activate = 1" >> /etc/ssl/openssl.cnf

# Integrar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Exponer puerto
EXPOSE 8080

# Comando de inicio
CMD php -S 0.0.0.0:8080 -t /var/www/html

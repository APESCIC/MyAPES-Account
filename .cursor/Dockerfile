FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        gnupg \
        jq \
        unzip \
        zip \
        php8.4-cli \
        php8.4-curl \
        php8.4-mbstring \
        php8.4-mysql \
        php8.4-redis \
        php8.4-sqlite3 \
        php8.4-xml \
        php8.4-zip \
    && rm -rf /var/lib/apt/lists/* \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/* \
    && curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN useradd -m -s /bin/bash ubuntu

USER ubuntu
WORKDIR /home/ubuntu

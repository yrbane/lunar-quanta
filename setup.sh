#!/usr/bin/env bash
apt-get update
apt-get install -y php-cli php-mbstring unzip
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
composer install
composer install --working-dir=tools

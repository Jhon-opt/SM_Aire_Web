#!/bin/bash
docker run --rm \
  -p 8080:80 \
  -v "$(dirname "$0")":/var/www/html \
  php:8.2-apache \
  bash -c "a2enmod rewrite && apache2-foreground"

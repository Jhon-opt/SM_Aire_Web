FROM php:8.2-apache

RUN a2enmod rewrite

RUN { \
      echo '<Directory /var/www/html/>'; \
      echo '  AllowOverride All'; \
      echo '  Require all granted'; \
      echo '</Directory>'; \
    } >> /etc/apache2/apache2.conf

COPY . /var/www/html/

EXPOSE 80

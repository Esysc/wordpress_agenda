# Test image for the upcoming WordPress 7.2 release.
# The official WordPress image provides the PHP/Apache runtime; core is
# replaced with the RC tarball until an official 7.2 image is published.
ARG WP_BASE_VERSION=7.1.5
ARG PHP_VERSION=8.3
FROM wordpress:${WP_BASE_VERSION}-php${PHP_VERSION}-apache

ARG WP_VERSION=7.2-RC1

RUN curl -fsSL "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz" \
        | tar -xz --strip-components=1 -C /usr/src/wordpress \
    && chown -R www-data:www-data /usr/src/wordpress

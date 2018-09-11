FROM gliderlabs/alpine:3.6

RUN apk update \
	&& apk upgrade \
	&& apk add --update \
	build-base \
	nginx \
        imagemagick \
	php7 php7-fpm php7-opcache php7-mbstring php7-curl php7-mcrypt php7-intl php7-mysqli php7-session php7-gettext php7-imagick-dev php7-pdo php7-pdo_mysql php7-json

COPY schema.sql schema.sql
COPY nginx.conf /etc/nginx/nginx.conf
COPY blog.conf /etc/nginx/conf.d/default.conf
COPY www.conf /etc/php7/php-fpm.d/www.conf
COPY php.ini /etc/php7/php.ini
COPY start.sh start.sh

CMD ["sh", "start.sh"]

FROM ubuntu:xenial

ENV LANG=C.UTF-8

RUN set -x \
	&& apt-get update \
	&& apt-get install --no-install-recommends --no-install-suggests -y software-properties-common python-software-properties gnupg2 dirmngr

RUN set -x \
	&& add-apt-repository -y ppa:ondrej/php \
    && add-apt-repository -y ppa:nginx/stable \
    && apt-get update

RUN set -x \
    && apt-get install --no-install-recommends --no-install-suggests -y php7.1-common php7.1-fpm php7.1-mbstring php7.1-curl php7.1-mcrypt php7.1-intl php7.1-mysql nginx-full mysql-client git

COPY schema.sql schema.sql
COPY nginx.conf /etc/nginx/nginx.conf
COPY blog.conf /etc/nginx/sites-available/default
COPY www.conf /etc/php/7.1/fpm/pool.d/www.conf
COPY php.ini /etc/php/7.1/fpm/php.ini
COPY start.sh start.sh

CMD ["bash", "start.sh"]

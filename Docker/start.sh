#!/bin/bash

mkdir -p -m 0700 /root/.ssh

# Prevent config files from being filled to infinity by force of stop and restart the container
echo "" > /root/.ssh/config
echo -e "Host *\n\tStrictHostKeyChecking no\n" >> /root/.ssh/config

if [[ "$GIT_USE_SSH" == "1" ]] ; then
  echo -e "Host *\n\tUser ${GIT_USERNAME}\n\n" >> /root/.ssh/config
fi

if [ ! -z "$SSH_KEY" ]; then
 echo $SSH_KEY > /root/.ssh/id_rsa.base64
 base64 -d /root/.ssh/id_rsa.base64 > /root/.ssh/id_rsa
 chmod 600 /root/.ssh/id_rsa
fi

# Setup git variables
if [ ! -z "$GIT_EMAIL" ]; then
 git config --global user.email "$GIT_EMAIL"
fi
if [ ! -z "$GIT_NAME" ]; then
 git config --global user.name "$GIT_NAME"
 git config --global push.default simple
fi

if [ ! -d "/var/www/vue-blog" ]; then
  mkdir -p -m 0755 /var/www/vue-blog
  mkdir -p -m 0755 /var/www/vue-blog/api
  mkdir -p -m 0755 /var/www/vue-blog/public
fi;

if [ ! -d "/var/run/php" ]; then
  mkdir -p -m 0755 /var/run/php
fi;

# Dont pull code down if the .git folder exists
if [ ! -d "/var/www/vue-blog/.git" ]; then
 # Pull down code from git for our site!
 if [ ! -z "$GIT_REPO" ]; then
   # Remove the test index file if you are pulling in a git repo
   if [ ! -z ${REMOVE_FILES} ] && [ ${REMOVE_FILES} == 0 ]; then
     echo "skiping removal of files"
   else
     rm -Rf /var/www/vue-blog/*
   fi
   GIT_COMMAND='git clone '
   if [ ! -z "$GIT_BRANCH" ]; then
     GIT_COMMAND=${GIT_COMMAND}" -b ${GIT_BRANCH}"
   fi

   if [ -z "$GIT_USERNAME" ] && [ -z "$GIT_PERSONAL_TOKEN" ]; then
     GIT_COMMAND=${GIT_COMMAND}" ${GIT_REPO}"
   else
    if [[ "$GIT_USE_SSH" == "1" ]]; then
      GIT_COMMAND=${GIT_COMMAND}" ${GIT_REPO}"
    else
      GIT_COMMAND=${GIT_COMMAND}" https://${GIT_USERNAME}:${GIT_PERSONAL_TOKEN}@${GIT_REPO}"
    fi
   fi
   ${GIT_COMMAND} /var/www/vue-blog || exit 1
   if [ -z "$SKIP_CHOWN" ]; then
     chown -Rf nginx.nginx /var/www/vue-blog
   fi
 fi
fi

env | sed "s/\(.*\)=\(.*\)/env[\1]='\2'/" >> /etc/php/7.1/fpm/pool.d/www.conf
mysql -uroot -pbhunter -hmysql < schema.sql
php-fpm7.1 -D
nginx -g 'daemon off;'


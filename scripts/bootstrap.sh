#!/usr/bin/env bash

# install java and utils
apt-get update
apt-get install tmux curl git apache2 libapache2-mod-php5 php5-pgsql php5 php5-cli php5-curl php5-common php5-gd php5-xdebug -y

# prepare apache
service apache2 stop
rm /var/www -Rf
ln -s /vagrant /var/www
sed -i -e 's/None/All/g' /etc/apache2/sites-available/default
sed -i -e 's/RUN_USER=www-data/RUN_USER=vagrant/g' /etc/apache2/envvars
sed -i -e 's/memory_limit.*/memory_limit=512M/g' /etc/php5/apache2/php.ini
sed -i -e 's/upload_max_filesize.*/upload_max_filesize=128M/g' /etc/php5/apache2/php.ini
sed -i -e 's/post_max_size.*/post_max_size=128M/g' /etc/php5/apache2/php.ini
sed -i -e 's/display_errors.*/display_erros=On/g' /etc/php5/apache2/php.ini
a2enmod rewrite
chown vagrant /var/lock/apache2 -Rf
service apache2 start

# prepare startup
sed -i -e 's/exit/#exit/g' /etc/rc.local
echo 'echo $(date) > /var/log/rc.log ' > /etc/rc.local
echo 'service apache2 start >> /var/log/rc.log 2>&1' >> /etc/rc.local
echo 'SUBSYSTEM=="bdi",ACTION=="add",RUN+="/vagrant/scripts/register.sh >> /var/log/rc.log 2>&1"' > /etc/udev/rules.d/50-vagrant.rules

# composer
cd /vagrant
[[ ! -e composer.phar ]] && su vagrant -c "curl -sS https://getcomposer.org/installer | php"
[[ ! -e vendor ]] && su vagrant -c "./composer.phar install"

# register the service
cd /vagrant && ./scripts/register.sh


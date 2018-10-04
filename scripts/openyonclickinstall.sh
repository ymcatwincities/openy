#!/bin/bash
# To get OpenY on DigitalOcean one-app LAMP 16.04 droplet run the command:
# bash < <(curl -s https://raw.githubusercontent.com/ymcatwincities/openy-project/8.1.x/scripts/openyonclickinstall.sh)
# as root user
printf "Hello, OpenY evaluator.\n"

printf "Installing OpenY into /var/www/html\n"

printf "\nMaking backup of existing /var/www/html folder to /var/www/html.bak\n"
sudo rm -rf /var/www/html.bak/html || true
sudo mv /var/www/html /var/www/html.bak || true

printf "\nInstalling composer\n"

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/bin --filename=composer

printf "\nInstalling drush 8.1.17. In case if you need newer version - install it manually, please.\n"
sleep 5

wget https://github.com/drush-ops/drush/releases/download/8.1.17/drush.phar
chmod +x drush.phar
sudo mv drush.phar /usr/local/bin/drush

printf "\nInstalling needed php extensions\n"
sudo apt-get -y update || true
sudo apt-get -y install php-mbstring php-curl php-zip unzip php-dom php-xml php-simplexml|| true

root_pass=$(awk -F\= '{gsub(/"/,"",$2);print $2}' /root/.digitalocean_password)
sudo mysql -uroot -p$root_pass -e "drop database drupal;" || true
sudo mysql -uroot -p$root_pass -e "create database drupal;" || true
sudo sed -i "s/www\/html/www\/html\/docroot/g" /etc/apache2/sites-enabled/000-default.conf
sudo a2enmod rewrite
sudo service apache2 restart

drush dl -y drupal --destination=/tmp --default-major=8 --drupal-project-rename=drupal
cd /tmp/drupal
drush si -y minimal --db-url=mysql://root:$root_pass@localhost/drupal && drush sql-drop -y

printf "\nPreparing OpenY code tree \n"
composer create-project ymcatwincities/openy-project:8.1.x-dev /var/www/html --no-interaction

cp /tmp/drupal/sites/default/settings.php /var/www/html/docroot/sites/default/settings.php
sudo mkdir /var/www/html/docroot/sites/default/files
echo "\$config['system.logging']['error_level'] = 'hide';" >> /var/www/html/docroot/sites/default/settings.php
sudo chmod -R 777 /var/www/html/docroot/sites/default/settings.php
sudo chmod -R 777 /var/www/html/docroot/sites/default/files

IP="$(ip addr | grep 'state UP' -A2 | tail -n1 | awk '{print $2}' | cut -f1  -d'/')"

printf "\nOpen http://$IP/core/install.php to proceed with OpenY installation.\n"

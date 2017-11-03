#!/bin/bash
echo "Hello, OpenY evaluator."
echo "Installing OpenY into /var/www/html"
echo "Making backup of existing /var/www/html folder to /var/www/html.bak"
sudo mv /var/www/html /var/www/html.bak
echo "Installing composer"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/bin --filename=composer
echo "Installing drush 8.1.15. In case if you need newer version - install it manually, please."
wget https://github.com/drush-ops/drush/releases/download/8.1.15/drush.phar
chmod +x drush.phar
sudo mv drush.phar /usr/local/bin/drush
echo "Installing needed php extensions"
sudp apt-get update
sudo apt-get -y install php-mbstring php-curl php-zip unzip

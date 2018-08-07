#!/bin/bash
# To get OpenY with Drupal core 8.3.x on DigitalOcean one-app LAMP 16.04 droplet fixed run the command:
# bash < <(curl -s https://raw.githubusercontent.com/ymcatwincities/openy-project/8.1.x/scripts/patches/run8.3.x.sh)
# as root user
cd /var/www/html
if [ ! -f docroot/core/lib/Drupal/Core/DrupalKernel.php ]; then
    echo "OpenY not found!"
    exit 1
fi
wget https://raw.githubusercontent.com/ymcatwincities/openy-project/8.1.x/scripts/patches/8.3.x.patch
sudo cp docroot/core/lib/Drupal/Core/DrupalKernel.php /var/backups/DrupalKernel.php || true
echo "Checking patch could be applied"

patch -p1 --dry-run < 8.3.x.patch | grep checking | wc -l
if [[ $(patch -p1 --dry-run < 8.3.x.patch | grep checking | wc -l) = 2 ]]; then
  echo "Patch is correct! OpenY detected correctly! Patching..."
  patch -p1 < 8.3.x.patch
  "OpenY was patched. Thanks for being with us."
  exit 1
fi
echo "You have OpenY installed elsewhere or wrong version. Please double check one more time."

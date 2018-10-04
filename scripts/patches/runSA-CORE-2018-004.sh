#!/bin/bash
# To get OpenY with Drupal core 8.4.x on DigitalOcean one-app LAMP 16.04 droplet fixed run the command:
# bash < <(curl -s https://raw.githubusercontent.com/ymcatwincities/openy-project/8.1.x/scripts/patches/runSA-CORE-2018-004.sh)
# as root user
cd /var/www/html
if [ ! -f docroot/core/modules/file/src/Element/ManagedFile.php ]; then
    echo "OpenY not found!"
    exit 1
fi


sudo cp docroot/core/lib/Drupal/Core/Security/RequestSanitizer.php /var/backups/RequestSanitizer.php || true
sudo cp docroot/core/modules/file/src/Element/ManagedFile.php /var/backups/ManagedFile.php || true

cd docroot
wget https://raw.githubusercontent.com/ymcatwincities/openy-project/8.1.x/scripts/patches/SA-CORE-2018-004.patch
echo "Checking patch could be applied"

patch -p1 --dry-run < SA-CORE-2018-004.patch | grep checking | wc -l
if [[ $(patch -p1 --dry-run < SA-CORE-2018-004.patch | grep checking | wc -l) = 2 ]]; then
  echo "Patch is correct! OpenY detected correctly! Patching..."
  patch -p1 < SA-CORE-2018-004.patch
  "OpenY was patched. Thanks for being with us."
  exit 1
fi
echo "You have OpenY installed elsewhere or wrong version. Please double check one more time."

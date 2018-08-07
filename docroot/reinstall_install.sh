#!/bin/sh

EXTRA_VARS="site_url=http://install.drupal.192.168.56.132.xip.io settings_site_folder=install mysql_db=drupal_install run_installation_process=false"

if [ "$1" = "--windows" ]; then
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "is_windows=true $EXTRA_VARS"
else
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "$EXTRA_VARS"
fi

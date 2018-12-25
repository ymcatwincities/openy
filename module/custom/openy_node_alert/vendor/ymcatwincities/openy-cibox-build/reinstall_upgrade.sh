#!/bin/sh

EXTRA_VARS="site_url=http://upgrade.drupal.192.168.56.132.xip.io workflow_type=sql settings_site_folder=upgrade mysql_db=drupal_upgrade stage_file_proxy=true staging_config_dir=sites/upgrade/config/staging sync_config_dir=sites/upgrade/config/sync"

if [ "$1" = "--windows" ]; then
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "is_windows=true $EXTRA_VARS"
else
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "$EXTRA_VARS"
fi

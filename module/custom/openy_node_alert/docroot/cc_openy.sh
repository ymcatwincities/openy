#!/bin/sh

#
# Profile Config Cleaner
#
# This script helps to maintain configuration for installation profiles in Drupal8. The main idea of this script is to
# avoid routine handwork of selecting appropriate config files.
#
# How to work with the script?
#
# Prepare your environment:
# 1. Please, have a look in config_clean.yml file.
# 2. Set your installation profile variable.
# 3. Set a path to your staging directory.
# 4. Add or remove config files you want to exclude from configuration.
#
# Export configuration:
# 1. Make sure you've committed you profile config directory or you've got a backup!
# 2. Run config_clean.sh
# 3. Run git status
# 4. Check your files. Probably, you'll need to remove some redundant files. If so, please, add these files to the
#    config_clean.yml exclude variable.


if [ "$1" = "--windows" ]; then
    time ansible-playbook --extra-vars='is_windows=true site_url=openy.192.168.56.132.xip.io staging="sites/openy/config/staging"' ./config_clean.yml -i 'localhost,' --connection=local
else
    time ansible-playbook --extra-vars='site_url=openy.192.168.56.132.xip.io staging="sites/openy/config/staging"' ./config_clean.yml -i 'localhost,' --connection=local
fi

#!/usr/bin/env bash

# Remove docroot folder from openy folder to avoid collision with drupal core.
echo Removing .git folders from vendor folder to avoid possible git issues.
rm -rf docroot/ > /dev/null 2>&1

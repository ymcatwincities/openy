#!/usr/bin/env bash

# Remove docroot folder from openy folder to avoid collision with drupal core.
# Script expects to be in openy/scripts/ but will check for openy.info.yml
# before removing any undesired docroot/ in the openy profile folder.
echo Removing docroot folder from openy project folder to avoid issues.
if [ -f "openy.info.yml" ]; then rm -rf docroot/ > /dev/null 2>&1; fi

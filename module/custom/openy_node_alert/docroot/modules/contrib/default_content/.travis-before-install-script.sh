if [ $(phpenv version-name) == '5.6' ]
then
  echo "always_populate_raw_post_data=-1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini;
fi

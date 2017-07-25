In order to compile css here are the necessary steps:

- enter vagrant
- go to theme directory `cd /var/www/docroot/themes/custom/ymca/`
- `sudo apt-get install ruby-compass`
- `sudo apt-get install bundler`
- `bundle install`
- `sudo bundle exec compass watch --poll`
- or if you need to compile css one time - `bundle exec compass compile`

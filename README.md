# Maintaining the theme.

1. Install Ruby 2.2.5:

`gpg --keyserver hkp://keys.gnupg.net --recv-keys 409B6B1796C275462A1703113804BB82D39DC0E3`

`\curl -sSL https://get.rvm.io | bash -s stable`

Restart sh client.

`rvm install ruby-2.2.5`

Set as default ruby:

`rvm --default use 2.2.5`

2. Install bundler

`sudo gem install bundler`

3. Go to the theme folder

`bundle install`

4. To compile css use

`bundler exec compass compile`

# Maintaining the theme.

In order to compile css here are the necessary steps:

1. Install Ruby 2.2.5:

`gpg --keyserver hkp://keys.gnupg.net --recv-keys 409B6B1796C275462A1703113804BB82D39DC0E3`

`\curl -sSL https://get.rvm.io | bash -s stable`

To start using RVM you need to run 
`source ~/.rvm/scripts/rvm`

If still not working restart sh client.

`rvm install ruby-2.2.5`

Set as default ruby:

`rvm --default use 2.2.5`

2. Install ruby-compass:

`sudo apt-get install ruby-compass`

3. Install bundler
   
`sudo gem install bundler`

4. Install autoprefixer

`sudo apt-get install ruby`ruby -e 'puts RUBY_VERSION[/\d+\.\d+/]'`-dev`

`sudo gem install autoprefixer-rails -v '6.4.1.1'`

5. Go to the theme folder
   
`bundle install`
   
If you see error like this `tmpdir': could not find a temporary directory (ArgumentError)`
Run command `sudo chmod o+t /tmp` and try again.

5. To compile css one time use

`bundler exec compass compile`

6. Compass watching changes

`bundler exec compass watch --poll`

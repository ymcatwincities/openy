Drupal Vagrant Dev box for OpenY support
======

This Vagrant Box allows you to make a contribution into OpenY in a few minutes. Just follow steps and then you'll know how to do it.

# Pre-requirements
* Install [Vagrant](https://www.vagrantup.com/downloads.html)
* Install [VirtualBox](https://www.virtualbox.org/wiki/Downloads)

### Windows users

* Install [Cygwin](https://servercheck.in/blog/running-ansible-within-windows)
* Run Cygwin as Administrator user.

# Usage
### 1. Vagrant provision
- Run Vagrant with full provisioning **(recommended)**
```sh
vagrant up --provision && vagrant ssh
```

### 2. Local build
#### Unix users
- Run commands
```sh
cd /var/www/docroot
sh reinstall.sh
```
- Open http://drupal.192.168.56.132.xip.io

#### Windows users
- Run commands
```sh
cd /var/www/docroot
sh reinstall.sh --windows
```
- Open http://drupal.192.168.56.132.xip.io

### 3. Contribute
- Change code only in `docroot/contrib/profiles/openy`, commit & push it into your fork
- Read [contribution guide](https://github.com/ymcatwincities/openy/blob/8.x-1.x/docs/Contributing.md) how to contribute

#### Host updates
By default your site will be accessible this url - http://drupal.192.168.56.132.xip.io.
If ```xip.io``` not working - create row with `192.168.56.132 drupal.192.168.56.132.xip.io` in `/etc/hosts`.

# Reinstall options
### Vanilla installation
In order to install OpenY with default settings:
```sh
cd /var/www/docroot
sh reinstall.sh
```
This site will be available at http://drupal.192.168.56.132.xip.io.

### Upgrade path installation
In order to install OpenY based on previous release + your updates:
```sh
cd /var/www/docroot
sh reinstall_upgrade.sh
```

This site will be available at http://upgrade.drupal.192.168.56.132.xip.io.

### Installation process
In order to get access to installation process:
```sh
cd /var/www/docroot
sh reinstall_install.sh
```

This site will be available at http://install.drupal.192.168.56.132.xip.io.

# Tools
### Adminer
**Adminer** for MySQL administration is not included to the project by default.
But you can download it from [Adminer site](https://www.adminer.org/#download) and put it to the project folder (near the folder **docroot**) on the host machine.
Credentials are: **drupal:drupal** or **root:root**.
```
http://192.168.56.132.xip.io/adminer.php
```

### PHP Profiler XHProf
It is installed by default, but to use it as Devel module integration use:
```sh
drush en devel -y
drush vset devel_xhprof_enabled 1
drush vset devel_xhprof_directory '/usr/share/php' && drush vset devel_xhprof_url '/xhprof_html/index.php'
ln -s /usr/share/php/xhprof_html xhprof_html
```
After `vset devel_xhprof_enabled` it could return an error about "Class 'XHProfRuns_Default' not found" - ignore it.

### Other
* XDebug
* Drush
* Docker
* Composer
* Adminer
* XHProf
* PHP Daemon
* PHP, SASS, JS sniffers/lints/hints

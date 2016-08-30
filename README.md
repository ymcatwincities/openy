YMCA Vagrant Dev box for CIBox support.
======

#Installation
* [Vagrant](https://www.vagrantup.com/downloads.html)
* [VirtualBox](https://www.virtualbox.org/wiki/Downloads)
* Useful Vagrant's plugins
  * [Hosts Updater](https://github.com/cogitatio/vagrant-hostsupdater)
  * [vbguest](https://github.com/dotless-de/vagrant-vbguest)


#Usage

```sh
vagrant up && vagrant ssh
```

# Docker SQL flow
How to reinstall local site from scratch
- vagrant up && vagrant ssh
- cd /var/www/docroot
- reinstall.sh # reinstalls ymca project and re-creates docker container with name `drupal`
- reinstall_all.sh # reinstalls all ymca, openy, redwing projects
- reinstall_openy.sh and reinstall_redwing.sh for reinstalling respective sites only.

Helpful commands
- docker images # list of available images
- docker ps -as # list of running containers
- docker rm -f <container_name_or_hash> # removes container
- docker rmi -f <image_name_or_hash> # removes image (all dependant containers should be removed beforehand)
- http://192.168.56.132/adminer.php?server=172.18.0.2&username=root access to local dev dbs

### Reinstall only YGTC site

Unix users
```sh
sudo sh reinstall.sh
```
Windows users
```sh
sudo sh reinstall.sh --windows
```

### Reinstall all sites: YGTC, OpenY, YMCA Redwing
Unix users
```sh
sudo sh reinstall_all.sh
```
Windows users
```sh
sudo sh reinstall_all.sh --windows
```


By default your site will be accessible by using this url. 

```
YGTC - http://drupal.192.168.56.132.xip.io
OpenY - http://openy.192.168.56.132.xip.io
YMCA Redwing - http://redwing.192.168.56.132.xip.io
```

If ```xip.io``` not working - create row with

```hosts
192.168.56.132 drupal.192.168.56.132.xip.io
192.168.56.132 openy.192.168.56.132.xip.io
192.168.56.132 redwing.192.168.56.132.xip.io
```

in ```/etc/hosts``` or just use another ServerName in apache.yml

If you have Vagrant HostUpdater plugin, your hosts file will be automatically updated.

VirtualBox additions
=====
For automatic update additions within guest, please install proper plugin

```sh
vagrant plugin install vagrant-vbguest
```


Tools
=====

* XDebug
* Drush
* Selenium 2
* Composer
* Adminer
* XHProf
* PHP Daemon
* PHP, SASS, JS sniffers/lints/hints

##Adminer
Adminer for mysql administration (credentials drupal:drupal and root:root)

```
http://192.168.56.112.xip.io/adminer.php
```

##PHP Profiler XHProf
It is installed by default, but to use it as Devel module integration use:
```sh
drush en devel -y
drush vset devel_xhprof_enabled 1
drush vset devel_xhprof_directory '/usr/share/php' && drush vset devel_xhprof_url '/xhprof_html/index.php'
ln -s /usr/share/php/xhprof_html xhprof_html
```
After `vset devel_xhprof_enabled` it could return an error about "Class 'XHProfRuns_Default' not found" - ignore it.


Linux Containers
=====

When your system enpowered with linux containers(lxc), you can speedup a lot of things by
using them and getting rid of virtualization.
For approaching lxc, please install vagrant plugin

```sh
vagrant plugin install vagrant-lxc
apt-get install redir lxc cgroup-bin
```
also you may need to apply this patch https://github.com/fgrehm/vagrant-lxc/pull/354

When your system is enpowered by apparmor, you should enable nfs mounts for your host
machine
Do that by editing ```/etc/apparmor.d/lxc/lxc-default``` file with one line

```ruby
profile lxc-container-default flags=(attach_disconnected,mediate_deleted) {
  ...
    mount options=(rw, bind, ro),
  ...
```
and reload apparmor service
```sh
sudo /etc/init.d/apparmor reload
```


and run the box by command

```sh
VAGRANT_CI=yes vagrant up
```

Do use 
```
VAGRANT_CI=yes
```
environment variable, if you got issues with all vagrant commands.


Windows Containers
=====

Install [Cygwin](https://servercheck.in/blog/running-ansible-within-windows) according to provided steps.

Run Cygwin as Administrator user.

Use default flow to up Vagrant but run `sh reinstall.yml --windows`

##Windows troubleshooting

If you will see error liek ```...[error 26] file is busy...``` during ```sh reinstall.sh``` modify that line:

before

```yml
name: Stage File Proxy settings
sudo: yes
lineinfile: dest='sites/default/settings.php' line='$conf[\"stage_file_proxy_origin\"] = \"{{ stage_file_proxy_url }}";'
```

after:

```yml
name: Copy settings.php
sudo: yes
shell: cp sites/default/settings.php /tmp/reinstall_settings.php

name: Stage File Proxy settings
sudo: yes
lineinfile: dest='sites/default/settings.php' line='$conf[\"stage_file_proxy_origin\"] = \"{{ stage_file_proxy_url }}\";'

name: Restore settings.php
sudo: yes
shell: cp /tmp/reinstall_settings.php sites/default/settings.php
```

### How to install & use Mailcatcher

Install RVM on the VM.

```
gpg --keyserver hkp://keys.gnupg.net --recv-keys 409B6B1796C275462A1703113804BB82D39DC0E3
\curl -sSL https://get.rvm.io | bash -s stable --ruby
```

Install Mailcatcher on the VM.

```
rvm default@mailcatcher --create do gem install mailcatcher
rvm wrapper default@mailcatcher --no-prefix mailcatcher catchmail
```

Configure PHP (php.ini). Use `which catchmail` to get path to `catchmail` in the system

```
sendmail_path = /home/vagrant/.rvm/bin/catchmail -f your@mail.com
```

Start mailcatcher (every time after system restart).

```
mailcatcher --http-ip=0.0.0.0
```

Now you can access Mailcatcher by address `http://localhost:1080/` on your local machine.

Go to `admin/config/system/mailsystem` and set `Select the default sender plugin` to `Default PHP mailer`

**Congratulations!** Now you can send email and test them with mailcatcher.
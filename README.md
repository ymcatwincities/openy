
Open Y Project
============================
Welcome to OpenY Project!

The [Open Y Project](http://www.openymca.org/) is a composer based installer for the [Open Y distribution](http://www.drupal.org/project/openy) and include CIBox development environment.

Development environment allows you to up Open Y in a few minutes.

What is included to development environment:
- [Install and Reinstall scripts for OpenY.](https://github.com/ymcatwincities/openy-cibox-build) 
- [Drupal Vagrant Dev box for OpenY.](https://github.com/ymcatwincities/openy-cibox-vm)

[![Total Downloads](https://poser.pugx.org/ymcatwincities/openy-project/downloads.png)](https://packagist.org/packages/ymcatwincities/openy-project)
[![Latest Stable Version](https://poser.pugx.org/ymcatwincities/openy-project/v/stable.png)](https://packagist.org/packages/ymcatwincities/openy-project)
[![License](https://poser.pugx.org/ymcatwincities/openy-project/license.svg)](https://www.gnu.org/licenses/gpl-2.0-standalone.en.html)


REQUIREMENTS
------------

### Install Composer    
If you do not have [Composer](http://getcomposer.org/), you may install it by following the [official instructions](https://getcomposer.org/download/).
    
For usage, see [the documentation](https://getcomposer.org/doc/).

### Install Vagrant and VirtualBox on your system.
- [Install instructions for Vagrant](https://www.vagrantup.com/downloads.html)
- [Install instructions for VirtualBox](https://www.virtualbox.org/wiki/Downloads)

#### Windows users

* Install [Cygwin](https://servercheck.in/blog/running-ansible-within-windows)
* Run Cygwin as Administrator user.

INSTALLATION
------------

## Set Up a Project with Composer

You can then install this project template using the following command:
~~~
composer create-project ymcatwincities/openy-project:8.1.x-dev MY_PROJECT --no-interaction
~~~

## Run Vagrant with full provisioning
This Vagrant Box (CIBox) allows you to make a contribution into OpenY in a few minutes.

Let's go to the project folder and run command: 
~~~
vagrant up --provision && vagrant ssh
~~~
By default vagrant ssh password: vagrant

After the work please use:
~~~
vagrant halt 
~~~
and when back to work use command:
~~~
vagrant up && vagrant ssh
~~~

## Install Open Y
Now you have the Drupal 8 with Open Y in the directory /var/www/docroot. 

We will move into the "docroot" directory to run install script:
~~~
cd /var/www/docroot
sh reinstal.sh
~~~

You can then access the Open Y through the following URL:
~~~
http://drupal.192.168.56.132.xip.io/
~~~
and to admin:
~~~
http://drupal.192.168.56.132.xip.io/user/
~~~
Use login: admin and password: openy

## How to create a project without CIBox development environment:
Sometimes we need only Drupal 8 and Open Y without development environment for this case you can use:
~~~
composer create-project ymcatwincities/openy-project:8.1.x-dev MY_PROJECT --no-interaction --no-dev
~~~
also if project installed with development environment you need to remove CIBox use command:
~~~
composer update --no-interaction --no-dev
~~~
All Vagrant Dev box and Build scripts files will be removed. 

DIRECTORY STRUCTURE
-------------------
      docroot/                        contains Drupal core
      docroot/profiles/contrib/openy  contains Open Y distribution

# Documentation and helpful information
Documentation about Open Y is available at [docs](https://github.com/ymcatwincities/openy/tree/8.x-1.x/docs). For details please visit [http://www.openymca.org](http://www.openymca.org).

For Development information please take a look at [docs/Development](https://github.com/ymcatwincities/openy/tree/8.x-1.x/docs/Development).

# License
OpenY Project is licensed under the [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0-standalone.en.html)
 License - see the [LICENSE file](https://github.com/ymcatwincities/openy-project/blob/8.1.x/LICENSE) for details.

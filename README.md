<p align="center">
  <a href="http://www.openymca.org">
    <img alt="react-router" src="https://www.ymcamn.org/themes/custom/ymca/img/ymca-logo.svg" width="144">
  </a>
</p>

<h3 align="center">
  Open YMCA
</h3>

<p align="center">
  An open source platform for YMCAs, by YMCAs built on <a href="drupal.org">Drupal</a>.
</p>

<p align="center">
  <a href="https://packagist.org/packages/ymcatwincities/openy-project"><img src="https://img.shields.io/packagist/dm/ymcatwincities/openy-project.svg?style=flat-square"></a>
  <a href="https://packagist.org/packages/ymcatwincities/openy-project"><img src="https://img.shields.io/packagist/v/ymcatwincities/openy-project.svg?style=flat-square"></a>
</p>

***

The [Open Y Project](http://www.openymca.org/) is a composer based installer for the [Open Y distribution](http://www.drupal.org/project/openy).


## Requirements
- Installed [Composer](https://getcomposer.org/download/)

#### 1. Composer    
If you do not have [Composer](http://getcomposer.org/), you may install it by following the [official instructions](https://getcomposer.org/download/).
    
For usage, see [the documentation](https://getcomposer.org/doc/).

#### 2. Windows users

* Install [Cygwin](https://servercheck.in/blog/running-ansible-within-windows)
* Run Cygwin as Administrator user.

## Installation

#### Latest STABLE version
```
composer create-project ymcatwincities/openy-project MY_PROJECT --no-interaction --no-dev
```

This command will build project based on [**latest stable**](https://github.com/ymcatwincities/openy/releases) release.

#### Latest DEVELOPMENT version
```
composer create-project ymcatwincities/openy-project:8.1.x-development-dev MY_PROJECT --no-interaction --no-dev
```

This command will build project based on [**latest development**](https://github.com/ymcatwincities/openy/commits/8.x-1.x) release.

INSTALLATION
------------

## Run on Vagrant with full provisioning
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

## Run on Docksal
Let's go to the project folder and run command: 
~~~
sh build.sh
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

## How to create a project without development environment:
Sometimes we need only Drupal 8 and Open Y without development environment for this case you can use:
~~~
composer create-project ymcatwincities/openy-project:8.1.x-dev MY_PROJECT --no-interaction --no-dev
~~~
also if project installed with development environment you need to remove CIBox and Docksal use command:
~~~
composer update --no-interaction --no-dev
~~~
All Docksal, Vagrant Dev box and Build scripts files will be removed. 

DIRECTORY STRUCTURE
-------------------
      docroot/                        contains Drupal core
      docroot/profiles/contrib/openy  contains Open Y distribution

# Documentation and helpful information
Documentation about Open Y is available at [docs](https://github.com/ymcatwincities/openy/tree/8.x-1.x/docs). For details please visit [http://www.openymca.org](http://www.openymca.org).

For Development information please take a look at [docs/Development](https://github.com/ymcatwincities/openy/tree/8.x-1.x/docs/Development).

### Video:
- [Introduce to OpenY](https://youtu.be/tXwbucW2TEQ)
- [How to use Docksal](https://youtu.be/jev2EW2hzdY)


# License
OpenY Project is licensed under the [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0-standalone.en.html)
 License - see the [LICENSE file](https://github.com/ymcatwincities/openy-project/blob/8.1.x/LICENSE) for details.

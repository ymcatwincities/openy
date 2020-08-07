If you need to prepare server for the Open Y instance, here below you should find all needed software to meet its requirements.

### List of requirements

1. Ubuntu LTS (14 or 16) is preferred. CentOS is ok as well. Or even any other Linux distribution, but was not tested by Open Y team so far.

2. (Drupal 8 server requirements should be met)[https://www.drupal.org/docs/system-requirements/php-requirements].

3. PHP 5.6+ (PHP 7 is better in terms of performance)
 
### List of PHP modules server should have:

  - php{{ php_version }}
  - php{{ php_version }}-mcrypt
  - php{{ php_version }}-cli
  - php{{ php_version }}-common
  - php{{ php_version }}-curl
  - php{{ php_version }}-dev
  - php{{ php_version }}-fpm
  - php{{ php_version }}-gd
  - php{{ php_version }}-mysql
  - php{{ php_version }}-memcached
  - php{{ php_version }}-imagick
  - php{{ php_version }}-xml
  - php{{ php_version }}-xdebug
  - php{{ php_version }}-mbstring
  - php{{ php_version }}-soap
  - php{{ php_version }}-zip

  
4. MySQL 5.5+ . Here are the best settings https://github.com/cibox/cibox/blob/master/core/facade-mysql/defaults/main.yml to get it fast and furious
5. Apache 2 with mod-php (preffered for stability) or nginx with php-fpm (better for speed and scalability)
  - libapache2-mod-php{{ php_version }}
6. Memcache server (optional)

7.  Server tools
 * Ansible (optional) 
 * Docker (optional)
 * SOLR 4.x (if there will be requirement for SOLR search support)
 * Varnish (optional)

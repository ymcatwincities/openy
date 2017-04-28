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

#### Composer    
If you do not have [Composer](http://getcomposer.org/), you may install it by following the [official instructions](https://getcomposer.org/download/).
    
For usage, see [the documentation](https://getcomposer.org/doc/).

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

## Development environment

You should use composer command without `--no-dev` if you would like to get environment that was configured especially for OpenY. So it should look like this:
```
composer create-project ymcatwincities/openy-project:8.1.x-development-dev MY_PROJECT --no-interaction
```

### CIBox VM
[CIBox VM](http://cibox.tools) allows you to make a contribution into OpenY in a few minutes. Just follow steps and then you'll know how to do it.

`TBD`

### Docksal

`TBD`

# Directory structure
| Directory | Purpose |
|-----------|---------|
| [**OpenY**](https://github.com/ymcatwincities/openy) ||
| `docroot/` | Contains Drupal core |
| `docroot/profiles/contrib/openy/` | Contains Open Y distribution |
| `vendor/` | Contains Open Y distribution |
| `composer.json` | Contains Open Y distribution |
| [**CIBox VM**](https://github.com/ymcatwincities/openy-cibox-vm) + [**CIBox Build**](https://github.com/ymcatwincities/openy-cibox-build)  ||
| `cibox/` | Contains CIBox libraries |
| `docroot/devops/` | DevOps scripts for the installation process |
| `provisioning/` | Vagrant configuration |
| `docroot/\*.sh` | Bash scripts to trigger reinstall scripts
| `docroot/\*.yml` | YAML playbooks for the installation process |
| `Vagrantfile` | Vagrant index file |
| [**Docksal**](https://github.com/ymcatwincities/openy-docksal) ||
| `.docksal/` | Docksal configuration |
| `build.sh` | Build script for Docksal environment |

# Documentation
Documentation about Open Y is available at [docs](https://github.com/ymcatwincities/openy/tree/8.x-1.x/docs). For details please visit [http://www.openymca.org](http://www.openymca.org).

For Development information please take a look at [docs/Development](https://github.com/ymcatwincities/openy/tree/8.x-1.x/docs/Development).

# License
OpenY Project is licensed under the [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0-standalone.en.html). See the [License file](https://github.com/ymcatwincities/openy-project/blob/8.1.x/LICENSE) for details.

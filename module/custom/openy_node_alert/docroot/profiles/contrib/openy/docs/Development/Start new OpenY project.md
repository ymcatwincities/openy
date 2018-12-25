Here you can find instructions how you can start project based on Open Y distribution.

# New project from scratch based on Open Y

In order to start new project from scratch, you can use [installation instructions](https://github.com/ymcatwincities/openy-project#installation) that will build your project and even add development environment.


# Add Open Y to existing Drupal 8 project

Please take a look at the full `composer.json` file below that you should eventually get.
<details> 
  <summary><strong>Example composer.json (Drupal 8.3.2 + Open Y 1.2)</strong></summary>

```
{
    "name": "drupal/drupal",
    "description": "Drupal is an open source content management platform powering millions of websites and applications.",
    "type": "project",
    "license": "GPL-2.0+",
    "require": {
        "composer/installers": "^1.0.24",
        "wikimedia/composer-merge-plugin": "~1.4",
        "ymcatwincities/openy": "8.*.*",
        "cweagans/composer-patches": "~1.0"
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "config": {
        "preferred-install": "dist",
        "autoloader-suffix": "Drupal8",
        "secure-http": false
    },
    "extra": {
        "_readme": [
            "By default Drupal loads the autoloader from ./vendor/autoload.php.",
            "To change the autoloader you can edit ./autoload.php.",
            "This file specifies the packages.drupal.org repository.",
            "You can read more about this composer repository at:",
            "https://www.drupal.org/node/2718229"
        ],
        "merge-plugin": {
            "include": [
                "core/composer.json"
            ],
            "recurse": false,
            "replace": false,
            "merge-extra": false
        },
        "installer-paths": {
          "core": ["type:drupal-core"],
          "libraries/{$name}": ["type:drupal-library"],
          "modules/contrib/{$name}": ["type:drupal-module"],
          "profiles/contrib/{$name}": ["type:drupal-profile"],
          "themes/contrib/{$name}": ["type:drupal-theme"],
          "drush/contrib/{$name}": ["type:drupal-drush"],
          "modules/custom/{$name}": ["type:drupal-custom-module"],
          "themes/custom/{$name}": ["type:drupal-custom-theme"]
        },
        "enable-patching": true
    },
    "autoload": {
        "psr-4": {
            "Drupal\\Core\\Composer\\": "core/lib/Drupal/Core/Composer"
        }
    },
    "scripts": {
        "pre-autoload-dump": "Drupal\\Core\\Composer\\Composer::preAutoloadDump",
        "post-autoload-dump": [
          "Drupal\\Core\\Composer\\Composer::ensureHtaccess"
        ],
        "post-package-install": "Drupal\\Core\\Composer\\Composer::vendorTestCodeCleanup",
        "post-package-update": "Drupal\\Core\\Composer\\Composer::vendorTestCodeCleanup",
        "post-install-cmd": [
            "bash scripts/remove_vendor_git_folders.sh || :"
        ],
        "post-update-cmd": [
            "bash scripts/remove_vendor_git_folders.sh || :"
        ]
    },
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        },
        {
            "type": "package",
            "package": {
                "name": "library-kenwheeler/slick",
                "version": "1.6.0",
                "type": "drupal-library",
                "source": {
                    "url": "https://github.com/kenwheeler/slick",
                    "type": "git",
                    "reference": "1.6.0"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "library-dinbror/blazy",
                "version": "1.8.2",
                "type": "drupal-library",
                "source": {
                    "url": "https://github.com/dinbror/blazy",
                    "type": "git",
                    "reference": "1.8.2"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "library-gdsmith/jquery.easing",
                "version": "1.4.1",
                "type": "drupal-library",
                "source": {
                    "url": "https://github.com/gdsmith/jquery.easing",
                    "type": "git",
                    "reference": "1.4.1"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "library-enyo/dropzone",
                "version": "4.3.0",
                "type": "drupal-library",
                "source": {
                    "url": "https://github.com/enyo/dropzone",
                    "type": "git",
                    "reference": "v4.3.0"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "library-jaypan/jquery_colorpicker",
                "version": "1.0.1",
                "type": "drupal-library",
                "source": {
                    "url": "https://github.com/jaypan/jquery_colorpicker",
                    "type": "git",
                    "reference": "da978ae124c57817021b3166a31881876882f5f9"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "library-ckeditor/panelbutton",
                "version": "4.7.0",
                "type": "drupal-library",
                "dist": {
                    "url": "http://download.ckeditor.com/panelbutton/releases/panelbutton_4.7.0.zip",
                    "type": "zip"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "library-ckeditor/colorbutton",
                "version": "4.7.0",
                "type": "drupal-library",
                "dist": {
                    "url": "http://download.ckeditor.com/colorbutton/releases/colorbutton_4.7.0.zip",
                    "type": "zip"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "library-ckeditor/colordialog",
                "version": "4.7.0",
                "type": "drupal-library",
                "dist": {
                    "url": "http://download.ckeditor.com/colordialog/releases/colordialog_4.7.0.zip",
                    "type": "zip"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "library-ckeditor/glyphicons",
                "version": "2.2",
                "type": "drupal-library",
                "dist": {
                    "url": "http://download.ckeditor.com/glyphicons/releases/glyphicons_2.2.zip",
                    "type": "zip"
                }
            }
        }
    ]
}
```

</details>

1. Add `"ymcatwincities/openy": "8.*.*"` to the `require` section in your `composer.json`, like [here](https://github.com/ymcatwincities/openy-project/blob/8.1.x/composer.json#L7) 

2. Add all required repositories that are [listed here](https://github.com/ymcatwincities/openy-project/blob/8.1.x/composer.json#L31) to your `composer.json`

3. Add installer path as here to your `composer json`. See [example](https://github.com/ymcatwincities/openy-project/blob/8.1.x/composer.json#L165).

- `composer.json` **inside** of docroot
Installer path will look like this:

    ```
    "installer-paths": {
        "core": ["type:drupal-core"],
        "libraries/{$name}": ["type:drupal-library"],
        "modules/contrib/{$name}": ["type:drupal-module"],
        "profiles/contrib/{$name}": ["type:drupal-profile"],
        "themes/contrib/{$name}": ["type:drupal-theme"],
        "drush/contrib/{$name}": ["type:drupal-drush"],
        "modules/custom/{$name}": ["type:drupal-custom-module"],
        "themes/custom/{$name}": ["type:drupal-custom-theme"]
    }
     ```

- `composer.json` **outside** of docroot
Installer path will look like this:

    ```
    "installer-paths": {
        "docroot/core": ["type:drupal-core"],
        "docroot/libraries/{$name}": ["type:drupal-library"],
        "docroot/modules/contrib/{$name}": ["type:drupal-module"],
        "docroot/profiles/contrib/{$name}": ["type:drupal-profile"],
        "docroot/themes/contrib/{$name}": ["type:drupal-theme"],
        "drush/contrib/{$name}": ["type:drupal-drush"],
        "docroot/modules/custom/{$name}": ["type:drupal-custom-module"],
        "docroot/themes/custom/{$name}": ["type:drupal-custom-theme"]
    }
    ```

4. Add `"cweagans/composer-patches": "~1.0"` to the `require` section in you `composer.json`. See [example](https://github.com/ymcatwincities/openy-project/blob/8.1.x/composer.json#L10).

5. Add `"enable-patching": true` to the `extra` section in your `composer.json` See [example](https://github.com/ymcatwincities/openy-project/blob/8.1.x/composer.json#L173).

6. Add `"secure-http": false` to the `config` section in your `composer.json` See [example](https://github.com/ymcatwincities/openy-project/blob/8.1.x/composer.json#L177).

7. Remove `composer.lock` and `vendor` folder from the project if they are exist in your folder.

8. Remove `"replace"` section from your `composer.json`

9. (Optional) If you keep `vendor` folder in your git repository, we recommend to clean up project from `.git` folder inside modules and libraries. To do so
- Add cleaner script to your project from [Open Y composer package](https://github.com/ymcatwincities/openy-project/blob/8.1.x/scripts/remove_vendor_git_folders.sh). You can just copy it and paste onto your project.
- [Adjust folders](https://github.com/ymcatwincities/openy-project/blob/8.1.x/scripts/remove_vendor_git_folders.sh#L4) that you would like to cleanup
- Execute it in `post-install-cmd` and `post-update-cmd`:

    ```
    "post-install-cmd": [
        "bash scripts/remove_vendor_git_folders.sh || :"
    ],
    "post-update-cmd": [
        "bash scripts/remove_vendor_git_folders.sh || :"
    ]
    ```

9. Run `composer install`

# [CIBox](https://github.com/cibox/cibox)

In this section you can learn how to configure development environment and CI server using Open Source product [CIBox](https://github.com/cibox/cibox).

### Create project

1. Generate project based on [this quickstart](http://docs.cibox.tools/en/latest/Quickstart/#prepare-github-project)
  
2. Add Open Y to the project using (Add Open Y to already existing Drupal 8 project)
  
3. Init git and add initial commit

  ```bash
  cd OPENY_PROJECT
  git init
  git commit -m "Init Open Y project"
  git remote add origin git@github.com:NAMESPACE/PROJECT.git
  git push -u origin master
  ```
4. Spin up your local vagrant machine

  ```bash
  vagrant up --provision
  ```

5. Setup CI server for new project based on [CIBox documentation](https://github.com/cibox/cibox#provision-new-ci-server).

* Follow quick start starting from Jenkins Provisioning Step http://docs.cibox.tools/en/latest/Quickstart/#jenkins-provisioning (Here we will get PR builds and DEMO site (DEV environment) with credentials to it )
* Setup hosting STAGE environment (it should be a 1:1 copy of existing or expected hosting account for ability to provide performance testing there)
* Setup deployment plans for CI by reusing DEMO builder job

# Install Open Y on [DigitalOcean](http://bit.ly/cibox-digitalocean)

1. Create new Droplet using "One-click apps" image `Drupal 8.*.* on 14.04`
2. Login to server [via SSH](https://www.digitalocean.com/community/tutorials/how-to-connect-to-your-droplet-with-ssh) or [web console](https://www.digitalocean.com/community/tutorials/how-to-use-the-digitalocean-console-to-access-your-droplet)
3. Run command

  ```bash
  bash <(curl -s https://raw.githubusercontent.com/ymcatwincities/openy/8.x-1.x/build/openy-digital-ocean.sh)
  ```
4. Open link(e.g. http://IP/core/install.php) from console output and finish Open Y installation

## Video tutorial
[![Open Y v1.0b - Install Tutorial](https://img.youtube.com/vi/RCvsLANsbm8/0.jpg)](https://youtu.be/RCvsLANsbm8)

## End to end installation
[![Open Y install - in 16 minutes end to end, no tutorial](https://img.youtube.com/vi/RT6kC38zgvo/0.jpg)](https://youtu.be/RT6kC38zgvo)

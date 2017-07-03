Start new OpenY project
=====

Here you can find instructions how you can start project based on OpenY distribution.

# New project from scratch based on OpenY

In order to start new project from scratch, you can use [installation instructions](https://github.com/ymcatwincities/openy-project#installation) that will build your project and even add development environment.


# Add OpenY to existing Drupal 8 project

1. Add `"ymcatwincities/openy": "8.*.*",` to the `require` section in your `composer.json`

2. Add all custom repositories that are listed here https://github.com/ymcatwincities/openy-project/blob/8.1.x/composer.json#L31 to your `composer.json`

3. Add installer path as here to your `composer json` https://github.com/ymcatwincities/openy-project/blob/8.1.x/composer.json#L165

- `composer.json` **inside** of docroot
Installer path will look like this:

    ```
    "installer-paths": {
        "core": ["type:drupal-core"],
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

4. Add `"cweagans/composer-patches": "~1.0"` to the `require` section in you `composer.json`

5. Add `"enable-patching": true` to the `extra` section in your `composer.json`

6. Remove `composer.lock` and `vendor` folder from the project

7. Remove `"replace"` section from your `composer.json`

8. (Optional) If you keep `vendor` folder in your git repository, we recommend to clean up project from `.git` folder inside modules and libraries. To do so
- Add cleaner script to your project from here https://github.com/ymcatwincities/openy-project/blob/8.1.x/scripts/remove_vendor_git_folders.sh
- Execute it in `post-install-cmd` and `post-update-cmd`:

    ```
    "post-install-cmd": [
        "bash scripts/remove_vendor_git_folders.sh || :"
    ],
    "post-update-cmd": [
        "bash scripts/remove_vendor_git_folders.sh || :"
    ],
    ```

9. Run `composer install`

# [CIBox](https://github.com/cibox/cibox)

In this section you can learn how to configure development environment and CI server using Open Source product [CIBox](https://github.com/cibox/cibox).

### Create project

1. Generate project based on [this quickstart](http://docs.cibox.tools/en/latest/Quickstart/#prepare-github-project)
  
2. Add OpenY to the project using (Add OpenY to already existing Drupal 8 project)
  
3. Init git and add initial commit

  ```bash
  cd OPENY_PROJECT
  git init
  git commit -m "Init OpenY project"
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

# Install OpenY on [DigitalOcean](http://bit.ly/cibox-digitalocean)

1. Create new Droplet using "One-click apps" image `Drupal 8.*.* on 14.04`
2. Login to server [via SSH](https://www.digitalocean.com/community/tutorials/how-to-connect-to-your-droplet-with-ssh) or [web console](https://www.digitalocean.com/community/tutorials/how-to-use-the-digitalocean-console-to-access-your-droplet)
3. Run command

  ```bash
  bash <(curl -s https://raw.githubusercontent.com/ymcatwincities/openy/8.x-1.x/build/openy-digital-ocean.sh)
  ```
4. Open link(e.g. http://IP/core/install.php) from console output and finish OpenY installation

## Video tutorial
[![Open Y v1.0b - Install Tutorial](https://img.youtube.com/vi/RCvsLANsbm8/0.jpg)](https://youtu.be/RCvsLANsbm8)

## End to end installation
[![Open Y install - in 16 minutes end to end, no tutorial](https://img.youtube.com/vi/RT6kC38zgvo/0.jpg)](https://youtu.be/RT6kC38zgvo)

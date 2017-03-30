Start new OpenY project
=====

Here you can find instructions how you can start project based on OpenY distribution.

# [CIBox](https://github.com/cibox/cibox)

In this section you can learn how to configure development environment and CI server using Open Source product [CIBox](https://github.com/cibox/cibox).

### Create project

1. Download [provisioned Vagrant for Drupal 8](http://openy-dev.ffwua.com/packages/ciboxvm_drupal8_openy_dev.zip)
  
  ```bash
  wget http://openy-dev.ffwua.com/packages/ciboxvm_drupal8_openy_dev.zip
  ```
  
2. Unzip archive

  ```bash
  unzip ciboxvm_drupal8_openy_dev.zip -d OPENY_PROJECT
  ```
  
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

# [Install OpenY on DigitalOcean](http://digitalocean.com)

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

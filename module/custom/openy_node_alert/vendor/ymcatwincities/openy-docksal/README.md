This is a Docksal-based for the [Open Y](https://github.com/ymcatwincities/openy) Drupal distribution.

[Docksal](https://docksal.io/) is used as a local development environment.

## Get Started

You need to install Docksal on your local machine according to [Docksal setup](http://docksal.readthedocs.io/en/master/getting-started/env-setup/) instructions.

### Full install from scratch

Note: a local instance of PHP and/or Composer is not required.

```bash
# Install Docksal
curl -fsSL https://get.docksal.io | bash
# Change directory to your workspace
fin run-cli composer create-project ymcatwincities/openy-project openy --no-interaction --no-dev
cd openy
fin run-cli composer install
fin init
```

You should see something like

```
MBP-Andrii:openy podarok$ fin init
 Step 1: Recreating services. 
Removing containers...
Removing network openy_default
WARNING: Network openy_default not found.
Removing volume openy_project_root
WARNING: Volume openy_project_root not found.
Volume docksal_ssh_agent is external, skipping
Starting services...
Creating network "openy_default" with the default driver
Creating volume "openy_project_root" with local driver
Pulling web (docksal/web:latest)...
latest: Pulling from docksal/web
88286f41530e: Pull complete
744e41c53ade: Pull complete
a81940df563c: Pull complete
ffce8fc48231: Pull complete
a8b049cfa846: Pull complete
fea7b994aec2: Pull complete
56467af11998: Pull complete
29d43475459c: Pull complete
b2680c5bb942: Pull complete
Digest: sha256:152faca86dad3736a5631333e999b8133d83cdb9d0aa411b46f95d12dd91d8bd
Status: Downloaded newer image for docksal/web:latest
Pulling db (docksal/db:latest)...
latest: Pulling from docksal/db
2a72cbf407d6: Pull complete
38680a9b47a8: Pull complete
4c732aa0eb1b: Pull complete
c5317a34eddd: Pull complete
f92be680366c: Pull complete
6762c4c3eacc: Pull complete
7f9e7799488e: Pull complete
5bfede7d51ce: Pull complete
4d58e230ee6f: Pull complete
e83cf84d215b: Pull complete
9a02cf99f495: Pull complete
6693741ec461: Pull complete
431ca06706e2: Pull complete
Digest: sha256:bb770c9e9d5d2897fe89473ab33d59e1fcb1717ae7be74a03119c014de368ac6
Status: Downloaded newer image for docksal/db:latest
Pulling cli (docksal/cli:edge-php7.1)...
edge-php7.1: Pulling from docksal/cli
f2aa67a397c4: Pulling fs layer
c533bdb78a46: Pulling fs layer
65a7293804ac: Pull complete
35a9c1f94aea: Pull complete
b6c541bc05dd: Pull complete
3a0ebd442fec: Pull complete
f6dc9c95b4d5: Pull complete
dd9cb9ca3529: Pull complete
5c7312d96c23: Pull complete
bd640ed5b036: Pull complete
ddbe6398edce: Pull complete
61077a0984a0: Pull complete
74951dc393a1: Pull complete
b7c72847ff47: Pull complete
c8c0e7a634ba: Pull complete
e2c8095a1a06: Pull complete
f04fd204a9c2: Pull complete
d9e07fddc42a: Pull complete
b62a0e1b7c9d: Pull complete
2f472dd03641: Pull complete
2657f4060957: Pull complete
35686158b3b3: Pull complete
1beb0fb2671f: Pull complete
6f38547f805f: Pull complete
ef3ac0591dd8: Pull complete
9bbf20269efe: Pull complete
6b7796e94a94: Pull complete
6c4581a142c4: Pull complete
3b5db81a74b2: Pull complete
e5a17dc73c2c: Pull complete
96e4e421816b: Pull complete
c1d22a2f38f2: Pull complete
b3866b41bcf8: Pull complete
467bde025869: Pull complete
14b97bdedf14: Pull complete
4ad3db22d991: Pull complete
Digest: sha256:8575b2bb305faa7cafaf209c259ad708431bf66a7f4ce3c08411854da211ac06
Status: Downloaded newer image for docksal/cli:edge-php7.1
Creating openy_cli_1 ... done
Creating openy_db_1  ... done
Creating openy_web_1 ... done
Waiting for openy_cli_1 to become ready...
Connected vhost-proxy to "openy_default" network.
 Step 2: Waiting 5s for MySQL to start. 
[==========]
 Step 3: Installing site. 
Install site from 'openy' profile.
You are about to create a /var/www/docroot/sites/default/settings.php file and DROP all tables in your 'default' database. Do you want to continue? (y/n): y
Starting Drupal installation. This takes a while. Consider using the --notify global option.                                           [ok]
error sending mail
2018/07/03 11:07:23 dial tcp 194.60.69.106:1025: getsockopt: connection timed out
Installation complete.                                                                                                                 [ok]
Unable to send email. Contact the site administrator if the problem persists.                                                          [error]
If you have not yet enabled any @font-your-face provider modules, please do so. If you have already enabled @font-your-face provider   [status]
modules, please use the font settings page in the appearance section to import fonts from them.
Created three GoogleTagManager snippet files based on configuration.                                                                   [status]
You may now install your own custom fonts. Remember to follow the EULA for any given font.                                             [status]
Optimizely database table has been created.                                                                                            [status]
A default project / experiment entry has been created.                                                                                 [status]
         Next, enter your Optimizely account ID on the module's ACCOUNT INFO page.
         There is also an Optimizely permission that can be set for specific roles
         to access the adminstration functionality.
         You can access those pages via the Optimizely module below.
You can now include content into the sitemap by visiting the corresponding entity type edit pages (e.g. node type edit pages).Support  [status]
for additional entity types and custom links can be added on the module's configuration pages.
The XML sitemap has been regenerated for all languages.                                                                                [status]
Congratulations, you installed Open Y!    
Clear caches.
Cache rebuild complete.                                                                                                                [ok]
Created three GoogleTagManager snippet files based on configuration.                                                                   [status]

real    22m8.914s
user    0m1.720s
sys     0m0.780s
 DONE!  Open http://openy.docksal in your browser to verify the setup.
```

Open the URL printed at the end of the setup (e.g. `http://openy.docksal`) to see your local copy of the latest stable Open Y.

# Docksal environment for Open Y

Open the project's folder and run one of the commands.

Administrator account is _admin_:_admin_.

### Start the project

```bash
fin init
```


The webserver starts up, and the site will be installed automatically with `drush si`.

### Install site from UI
```bash
fin install_steps
```

The webserver starts up and for site will be provided base configuration. 
After finish you need to open site in browser and continue installation from UI.
This command is useful for testing Open Y install form.

### Testing Upgrade path
```bash
fin upgrade_init
```

The webserver starts up and the site will be installed from Open Y DB dump 
that contain pre-installed previous Open Y version.
After installation will be executed all new updates that were added in the latest Open Y versions.

More information about the upgrade path you can find here - [How to support upgrade path](https://github.com/ymcatwincities/openy/blob/8.x-2.x/docs/Development/Upgrade%20path.md)


# How to develop?

After you run the "fin init" and have your environment ready you need to do few things.

- Create a fork of [http://github.com/ymcatwincities/openy](http://github.com/ymcatwincities/openy).
- In your project go to `docroot/profiles/contrib/openy` and edit `.git/config` file. Replace repo URL to your newly created fork.
- Then you can create a branch in your repo, push some code and create a pull request back to `ymcatwincities/openy` repo.

# How to run behat tests?

Edit `behat.local.yml` and set `base_url` to `web` and `wd_host` to `http://browser:4444/wd/hub`. 
Then you can run your behat tests with `./vendor/behat/behat/bin/behat`.

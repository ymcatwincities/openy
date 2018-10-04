# Ludwig

## The problem
Drupal modules often require external PHP libraries.
Due to the way Composer works, these libraries can't be manually uploaded to the site's vendor folder.
Instead, Composer must be used to download the module, which then pulls in the required libraries.
Once Composer is used to manage a single module, it also needs to be used to manage and update Drupal core,
since manual Drupal core updates replace the vendor/ folder, removing the downloaded libraries.

Even though we fully believe in the superiority of a Composer-based approach to site management,
we still want to offer an alternative to users who fear the command line or want to avoid it
for other reasons. Ludwig was written for them.

## The solution
Modules provide a ludwig.json file which lists all of their required libraries:
```
{
  "require": {
    "doctrine/collections": {
      "version" : "v1.4.0",
      "url": "https://github.com/doctrine/collections/archive/v1.4.0.zip"
    },
    "commerceguys/enum": {
      "version" : "v1.0",
      "url": "https://github.com/commerceguys/enum/archive/v1.0.zip"
    },
    "commerceguys/addressing": {
      "version" : "v1.0.0-beta3",
      "url": "https://github.com/commerceguys/addressing/archive/v1.0.0-beta3.zip"
    }
  }
}
```
This list must also include the libraries' dependencies, since no resolving takes place.
In our example, the first two libraries are required by commerceguys/addressing.

The site administrator can then download the libraries from the given links, and place them
in the module's lib folder:
- lib/doctrine-collections/v1.4.0/
- lib/commerceguys-enum/v1.0/
- lib/commerceguys-addressing/v1.0.0-beta3/

After a cache clear, Ludwig's ServiceProvider detects the subfolders and adds them as PSR-4 roots to Drupal's autoloader.

Requiring explicit versions, and having version subfolders means that the module can increase the
required version in a new release, which then forces the site administrator to download that
new release, since the library is suddenly no longer found.

## Commands

### Drupal Console
- ludwig:download: Download missing packages.
- ludwig:list: List all managed packages.

### Drush
- ludwig-download: Download missing packages.

## Name origin
Ludwig van Beethoven was a **deaf composer**.


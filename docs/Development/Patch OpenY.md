Here you can find instructions how you can patch OpenY distribution used on 
your project.

# When you need to patch OpenY
- In case you found a bug and prepared a patch for OpenY on github.
- In case you developed a new feature that will be good to have in OpenY and 
created Pull Request to OpenY repository
- In case you want to add a feature that added to OpenY but not included yet to 
OpenY release.

# How to patch OpenY via composer?

If you followed instructions [docs/Development/Start new OpenY project](https://github.com/ymcatwincities/openy/blob/8.x-1.x/docs/Development/Start%20new%20OpenY%20project.md)
and you have configured `composer.json` you need just to do a few simple steps:
1. Build a link to a patch using pull request ID
    ```
    https://patch-diff.githubusercontent.com/raw/ymcatwincities/openy/pull/XXX.patch
    ```
Where XXX is a number of pull request you want to use. 

2. Add a new section `patches` to the section `extra` and add a patch to OpenY 
repository, as on this example:
    ```
    "extra": {
        "installer-paths": {
          ...
        },
        "enable-patching": true,
        "patches": {
            "ymcatwincities/openy": {
                "Patch description": "https://patch-diff.githubusercontent.com/raw/ymcatwincities/openy/pull/XXX.patch"
            }
        }
    }
    ```
3. After adding a patch execute command `composer update`
4. Verify you can see added changes in OpenY
5. Enjoy!

### Site installation with drush

Use [drush site-install](https://drushcommands.com/drush-8x/core/site-install/) command.

Basically you use something like this:
```
drush site-install openy --account-pass=password --db-url="mysql://user:pass@host:3306/db" --root=/var/www/docroot
```

Complete Open Y profile preset and Open Y Rose theme is used in this case.

You can set which preset must be installed by specifying it with `openy_configure_profile.preset` variable, and theme with 
`openy_theme_select.theme`variable e.g.:
```
drush site-install openy --account-pass=password --db-url="mysql://user:pass@host:3306/db" --root=/var/www/docroot openy_configure_profile.preset=extended openy_theme_select.theme=openy_rose
```

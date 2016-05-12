[//]: # ( clear&&curl -s -F input_files[]=@INSTALL.md -F from=markdown -F to=html http://c.docverter.com/convert|tail -n+11|head -n-2 )
[//]: # ( curl -s -F input_files[]=@INSTALL.md -F from=markdown -F to=pdf http://c.docverter.com/convert>INSTALL.pdf )

# Installation

The Acquia Purge module provides integration with the [Purge module](https://www.drupal.org/project/purge)
and makes it extremely simple to achieve accurate, efficient cache invalidation
on your Acquia Cloud site.

Setting it all up shouldn't take long:

1. Download and enable the required modules: ``drush en acquia_purge --yes``

2. Add the "Acquia Cloud" purger at ``/admin/config/development/performance/purge``.

3. Verify if there are no diagnostic issues by running ``drush p-diagnostics``.

Do you have any questions, bugs or comments? Feel free to lookup common
questions in the ``FAQ.md`` file or file a issue on Drupal.org.

### Tuning

By strict design and principle, this module doesn't have any UI exposed settings
or configuration forms. The reason behind this philosophy is that - as a pure -
utility module only site administrators should be able to change anything and if
they do, things should be traceable in ``settings.php``. Although Acquia Purge
attempts to stay as turnkey and zeroconf as possible, the following options
exist as of this version and documented below:

```
╔══════════════════════════╦═══════╦═══════════════════════════════════════════╗
║      $settings key       ║ Deflt ║               Description                 ║
╠══════════════════════════╬═══════╬═══════════════════════════════════════════╣
║ acquia_purge_token       ║ FALSE ║ If set, this allows you to set a custom   ║
║                          ║       ║ X-Acquia-Purge header value. This helps   ║
║                          ║       ║ offset DDOS style attacks but requires    ║
║                          ║       ║ balancer level configuration chances for  ║
║                          ║       ║ you need to contact Acquia Support.       ║
║                          ║       ║ $settings['acquia_purge_token'] = 'secret'║
╚══════════════════════════╩═══════╩═══════════════════════════════════════════╝
```

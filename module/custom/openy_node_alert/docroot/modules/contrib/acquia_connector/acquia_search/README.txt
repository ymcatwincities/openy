Acquia Search module
================================================================================

Provides integration between your Drupal site and Acquia's hosted search
service, Acquia Search [1]. Requires Search API Solr module.

[1] https://docs.acquia.com/acquia-search/

Notes on Acquia Search data protection and index auto-switching
---------------------------------------------------------------

Acquia Search module attempts to auto-detect your environment and automatically
connect to the best-fit Acquia Search index available. This is done to attempt
to protect your data in your production Solr instance; otherwise a development
site could easily overwrite or delete the data in your production index.

This functionality was previously available as a third-party module
https://www.drupal.org/project/acquia_search_multi_subs which will now become
deprecated.

Depending on the indexes already provisioned on your Acquia Subscription, the
module will follow these rules to connect to the proper index:

* If your site is running within Acquia Cloud, Acquia Search will connect to
  the index name that matches the current environment (dev/stage/prod) and
  current multi-site instance.
* If the module can't find an appropriate index above, it will then enforce
  READ-ONLY mode on the production Solr index. This allows you to still test
  searching from any site while protecting your production, user-facing index.

The current state is noted on the Drupal UI's general status report at
/admin/reports/status, as well as when attempting to edit each connection.

You can override this behavior using code snippets or a Drupal variable. This,
however, poses risks to your data that you should be aware of.  Please consult
our documentation at https://docs.acquia.com/acquia-search/multiple-cores to
find out more.

Hidden settings
----------------
- acquia_search.settings.disable_auto_switch
    Boolean value; if TRUE, completely disables the auto-switching behavior
    and will let the site connect to the main/production Solr index normally
    (in read-write mode). Also, if TRUE, the disable_read_only setting below
     is ignored.

   Example settings.php override:
   # $config['acquia_search.settings']['disable_auto_switch'] = true;

- acquia_search.settings.disable_auto_read_only
    Boolean value; if TRUE (only when disable_auto_switch is FALSE or not set)
    then there is no enforcing of read-only mode. This means, regardless whether
    a proper index was found or not, no read-only enforcement will happen.

   Example settings.php override:
   # $config['acquia_search.settings']['disable_auto_read_only'] = true;

- acquia_search.settings.connection_override
    Array that overrides the Acquia Search connection for all your Search API
    Servers using Acquia Search. These overrides are applied at a later stage
    and overrides any and all module behavior from auto-switching or any settings
    done via the UI.

    Here's an example for settings.php:

    # Make all Acquia Search connections connect to a certain index.
    # NOTE: This requires overriding the: scheme, host, port, index_id and derived_key
    $config['acquia_search.settings']['connection_override'] = [
      'scheme' => 'http',
      'host' => 'somehostname.acquia-search.com',
      'index_id' => 'ABCD-12345.prod.mysite',
      'port' => 80,
      'derived_key' => 'asdfasdfasdfasdfasdfasdfasdfasdf
    ];

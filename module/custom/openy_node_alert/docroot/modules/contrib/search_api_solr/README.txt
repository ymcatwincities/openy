Installation
------------

The search_api_solr module manages its dependencies and class loader via
composer. So if you simply downloaded this module from drupal.org you have to
delete it and install it again via composer!

Simply change into Drupal directory and use composer to install search_api_solr:

cd $DRUPAL
composer require drupal/search_api_solr

Solr search
-----------

This module provides an implementation of the Search API which uses an Apache
Solr search server for indexing and searching. Before enabling or using this
module, you'll have to follow the instructions given in INSTALL.txt first.

The minimum support version for Search API Solr Search 8.x is Solr 4.5.1.
Any version below might work if you use your own Solr config.
For better performance and more features, 6.x should be used!
The support for 4.x and 5.x is deprecated and will be removed in 8.x-2.x.

For more detailed documentation, see the handbook [2].

[2] https://drupal.org/node/1999280

Supported optional features
---------------------------

All Search API datatypes are supported by using appropriate Solr datatypes for
indexing them. By default, "String"/"URI" and "Integer"/"Duration" are defined
equivalently. However, through manual configuration of the used schema.xml this
can be changed arbitrarily. Using your own Solr extensions is thereby also
possible.

The "direct" parse mode for queries will result in the keys being directly used
as the query to Solr. For details about Lucene's query syntax, see [3]. There
are also some Solr additions to this, listed at [4]. Note however that, by
default, this module uses the dismax query handler, so searches like
"field:value" won't work with the "direct" mode.

[3] http://lucene.apache.org/java/2_9_1/queryparsersyntax.html
[4] http://wiki.apache.org/solr/SolrQuerySyntax

Regarding third-party features, the following are supported:

- autocomplete
  Introduced by module: search_api_autocomplete
  Lets you add autocompletion capabilities to search forms on the site. (See
  also "Hidden variables" below for Solr-specific customization.)
- facets
  Introduced by module: facet
  Allows you to create facetted searches for dynamically filtering search
  results.
- more like this
  Introduced by module: search_api
  Lets you display items that are similar to a given one. Use, e.g., to create
  a "More like this" block for node pages build with Views.
- multisite
  Introduced by module: search_api_solr
- spellcheck
  Introduced by module: search_api_solr

If you feel some service option is missing, or have other ideas for improving
this implementation, please file a feature request in the project's issue queue,
at [5].

[5] https://drupal.org/project/issues/search_api_solr

Specifics
---------

Please consider that, since Solr handles tokenizing, stemming and other
preprocessing tasks, activating any preprocessors in a search index' settings is
usually not needed or even cumbersome. If you are adding an index to a Solr
server you should therefore then disable all processors which handle such
classic preprocessing tasks. Enabling the HTML filter can be useful, though, as
the default config files included in this module don't handle stripping out HTML
tags.

Hidden variables
----------------

- search_api_solr.settings.index_prefix (default: '')
  By default, the index ID in the Solr server is the same as the index's machine
  name in Drupal. This setting will let you specify a prefix for the index IDs
  on this Drupal installation. Only use alphanumeric characters and underscores.
  Since changing the prefix makes the currently indexed data inaccessible, you
  should change this vairable only when no indexes are currently on any Solr
  servers.
- search_api_solr.settings.index_prefix_INDEX_ID (default: '')
  Same as above, but a per-index prefix. Use the index's machine name as
  INDEX_ID in the variable name. Per-index prefixing is done before the global
  prefix is added, so the global prefix will come first in the final name:
  (GLOBAL_PREFIX)(INDEX_PREFIX)(INDEX_ID)
  The same rules as above apply for setting the prefix.
- search_api_solr.settings.cron_action (default: "spellcheck")
  The Search API Solr Search module can automatically execute some upkeep
  operations daily during cron runs. This variable determines what particular
  operation is carried out.
  - spellcheck: The "default" spellcheck dictionary used by Solr will be rebuilt
  so that spellchecking reflects the latest index state.
  - optimize: An "optimize" operation [9] is executed on the Solr server. As a
  result of this, all spellcheck dictionaries (that have "buildOnOptimize" set
  to "true") will be rebuilt, too.
  - none: No action is executed.
  If an unknown setting is encountered, it is interpreted as "none".
- search_api_solr.settings.site_hash (default: random)
  A unique hash specific to the local site, created the first time it is needed.
  Only change this if you want to display another server's results and you know
  what you are doing. Old indexed items will be lost when the hash is changed
  (without being automatically deleted from the Solr server!) and all items will
  have to be reindexed. Should only contain alphanumeric characters.

[9] http://wiki.apache.org/solr/UpdateXmlMessages#A.22commit.22_and_.22optimize.22

Connectors
----------

The communication details between Drupal and Solr is implemented by connectors.
This module includes the StandardSolrConnector and the BasicAuthSolrConnector.
There're service provider specific connectors available, for example from Acquia
and platform.sh. Pleas contact your provider for details if you don't run your
own Solr server.

Customizing your Solr server
----------------------------

The schema.xml and solrconfig.xml files contain extensive comments on how to
add additional features or modify behaviour, e.g., for adding a language-
specific stemmer or a stopword list.
But whenever you run a site that uses any language different than English or a
multi-lingual setup, we suggest that you don't modify the configurations by
yourself. Instead you should use the Search API Multilingual Solr Search
backend [10].
If you are interested in further customizing your Solr server to your needs,
see the Solr wiki at [11] for documentation. When editing the schema.xml and
solrconfig.xml files, please only edit the copies in the Solr configuration
directory, not directly the ones provided with this module.

[10] https://drupal.org/project/search_api_solr_multilingual
[11] http://wiki.apache.org/solr/

NOTE! You'll have to restart your Solr server after making such changes, for
them to take effect!

Troubleshooting Views
---------------------

When displaying search results from Solr in Views using the Search API Views
integration, make sure to *disable the Views cache*. By default the Solr search
index is updated asynchronously from Drupal, and this interferes with the Views
cache. Having the cache enabled will cause stale results to be shown, and new
content might not show up at all.

For most typical use cases the best results are achieved by disabling the Views
cache.

In case you really need caching (for example because you are showing some search
results on your front page) then you use the 'Search API (time based)' cache
plugin. This will make sure the cache is cleared at certain time intervals, so
your results will remain relevant. This can work well for views that have no
exposed filters and are set up by site administrators.

*Do not use the 'Search API (tag based)' cache!*
This is not compatible with default Solr setups.

Developers
----------

Whenever you need to enhance the functionality you should do it using the API
instead of extending the SearchApiSolrBackend class!
To customize connection-specific things you should provide your own
implementation of the \Drupal\search_api_solr\SolrBackendInterface.

Running the test suite
----------------------

This module comes with a suite of automated tests. To execute those, you just
need to have a (correctly configured) Solr instance running at the following
address:
  http://localhost:8983/solr/d8
(This represents a core named "d8" in a default installation of Solr.)

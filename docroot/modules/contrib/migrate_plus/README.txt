The migrate_plus module extends the core migration system with API enhancements
and additional functionality, as well as providing practical examples.

Extensions to base API
======================
* A Migration configuration entity is provided, enabling persistance of dynamic
  migration configuration.
* A MigrationGroup configuration entity is provided, which enables migrations to
  be organized in groups, and to maintain shared configuration in one place.
* A MigrateEvents::PREPARE_ROW event is provided to dispatch hook_prepare_row()
  invocations as events.
* A SourcePluginExtension class is provided, enabling one to define fields and
  IDs for a source plugin via configuration rather than requiring PHP code.

Plugin types
============
migrate_plus provides the following plugin types, for use with the url source
plugin.

* A data_parser type, for parsing different formats on behalf of the url source
  plugin.
* A data_fetcher type, for fetching data to feed into a data_parser plugin.
* An authentication type, for adding authentication headers with the http
  data_fetcher plugin.

Plugins
=======

Process
-------
* The entity_lookup process plugin allows you to populate references to entities
  which already exist in Drupal, whether they were migrated or not.
* The entity_generate process plugin extends entity_lookup to also create the
  desired entity when it doesn't already exist.
* The file_blob process plugin supports creating file entities from blob data.
* The merge process plugin allows the merging of multiple arrays into a single
  field.
* The skip_on_value process plugin allows you to skip a row, or a given field,
  for specific source values.

Source
------
* A url source plugin is provided, implementing a common structure for
  file-based data providers.

Data parsers
------------
* The xml parser plugin uses PHP's XMLReader interface to incrementally parse
  XML files. This should be used for XML sources which are potentially very
  large.
* The simple_xml parser plugin uses PHP's SimpleXML interface to fully parse
  XML files. This should be used for XML sources where you need to be able to
  use complex xpaths for your item selectors, or have to access elements outside
  of the current item element via xpaths.
* The json parser plugin supports JSON sources.
* The soap parser plugin supports SOAP sources.

Data fetchers
-------------
* The file fetcher plugin works for most URLs regardless of protocol, as well as
  local filesystems.
* The http fetcher plugin provides the ability to add headers to an HTTP
  request (particularly through authentication plugins).

Authentication
--------------
* The basic authentication plugin provides HTTP Basic authentication.
* The digest authentication plugin provides HTTP Digest authentication.
* The oauth2 authentication plugin provides OAuth2 authentication over HTTP.

Examples
========
* The migrate_example submodule provides a fully functional and runnable
example migration scenario demonstrating the basic concepts and most common
techniques for SQL-based migrations.
* The migrate_example_advanced submodule provides examples of migration from
different kinds of sources, as well as less common techniques.

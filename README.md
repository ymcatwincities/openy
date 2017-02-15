Custom Formatters
=================

The Custom Formatters module allows users to easily create custom Field
Formatters without the need to write a custom module. Custom Formatters can then
be exported as Drupal configuration entities.



Features
--------

* Pluggable formatter types:
    * **Formatter presets**  
      Create simple formatters from existing formatters with preset formatter
      settings.
    
    * **HTML + Tokens**  
      A HTML based editor with Token support.
    
    * **PHP**  
      A PHP based editor with support for multiple fields and multiple values.
    
    * **Twig**  
      A Twig based editor with support for multiple fields and multiple values.
    
* Supports for all fieldable entities, including but not limited to:
    * Drupal core - Comment, Node, Taxonomy term and User entities.
    * Field collection module - Field-collection item entity.
    * Media module - Media entity.
    
* Exportable as:
    * Drupal configuration entities.
        
* Integrates with:
    * **Contextual links** _(Drupal core)_
      Adds a hover link for quick editing of Custom Formatters.

    * **Token**  
      Adds the Token tree browser to the HTML + Tokens engine.



Recommended Modules
-------------------

* [Field tokens](http://drupal.org/project/field_tokens)
* [Token](http://drupal.org/project/token)



Usage/Configuration
-------------------

Read the manual at: [drupal.org/node/2514412](https://www.drupal.org/node/2514412)



Makefile entries
----------------

For easy downloading of Custom Formatters and it's required/recommended modules
and/or libraries, you can use the following entries in your makefile:


      projects:
        custom_formatters
        field_tokens
        token


**Note:** It is highly recommended to specify the version of your projects, the
above format is only for the sake of simplicity.



Testing / DCIR
--------------

This project is configured for testing via the Drupal common CI Runner (DCIR).

To run DCIR, simply run the following command from the project directory.

`docker run -v $(pwd):/dcir -it deciphered/dcir:latest`



TODOs / Roadmap
---------------

* Add Contextual links configuration as formatter setting.
* Add Dependency definition to Formatter form.
* Add granular permissions to Formatter types.
* Add Formatter list view?
  - Would require adding support for Formatter config entities in Views.
* Add custom support for Seven theme / Formatter add page.
* Add ability to change field types that aren't in use.
* Set usages of formatters to default formatter on deletion.
* Re-add save & edit?
* Re-add preview.
* Re-add export?
* Tests:
  - Ensure that if a formatter is in used it's field type can't change.
  - Add test for configuration dependencies.
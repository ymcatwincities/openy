README.txt
==========

Paragraph is a module to create paragraphs in your content.
You can create types(with own display and fields) as paragraph types.

When you use the Entity Reference Paragraphs widget + Entity Reference selection
type on your node/entity, you can select the allowed types, and when using the
widget, you can select a paragraph type from the allowed types to use
different fields/display per paragraph.

* Different fields per paragraph type
* Using different paragraph types in a single paragraph field
* Displays per paragraph type

CONFIGURATION
-------------
 * Enable the Paragraph module.

 * Add new languages for the translation in Configuration » Languages.

 * Enable any custom content type with a paragraph field to be translatable in
 Configuration » Content language
 and translation:

   - Under Custom language settings check Content.

      - Under Content check the content type with a paragraph field.

   - Make sure that the paragraph field is set to NOT translatable.

   - Set the fields of each paragraph type to translatable as required.

 * Check Paragraphs as the embedded reference in Configuration » Translation
 Management settings.

 * Create a new content - Paragraphed article and translate it.


LIMITATION
-------------
For now, this module does not support switching entity reference revision field
of the paragraph itself into multilingual mode. This would raise complexity
significantly.
Check #2461695: Support translatable paragraph entity reference revision field
(https://www.drupal.org/node/2461695).

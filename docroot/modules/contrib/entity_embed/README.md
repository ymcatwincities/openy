# Entity Embed Module

[![Travis build status](https://img.shields.io/travis/drupal-media/entity_embed/8.x-1.x.svg)](https://travis-ci.org/drupal-media/entity_embed) [![Scrutinizer code quality](https://img.shields.io/scrutinizer/g/drupal-media/entity_embed/8.x-1.x.svg)](https://scrutinizer-ci.com/g/drupal-media/entity_embed)

[Entity Embed](https://www.drupal.org/project/entity_embed) module allows any entity to be embedded using a text editor.

## Requirements

* Drupal 8
* [Embed](https://www.drupal.org/project/embed) module

## Installation

Entity Embed can be installed via the [standard Drupal installation process](http://drupal.org/node/895232).

## Configuration

* Install and enable [Embed](https://www.drupal.org/project/embed) module.
* Install and enable [Entity Embed](https://www.drupal.org/project/entity_embed) module.
* Go to the 'Text formats and editors' configuration page: `/admin/config/content/formats`, and for each text format/editor combo where you want to embed entities, do the following:
  * Enable the 'Display embedded entities' filter.
  * Drag and drop the 'E' button into the Active toolbar.
  * If the text format uses the 'Limit allowed HTML tags and correct faulty HTML' filter, ensure the necessary tags and attributes are whitelisted: add ```<drupal-entity data-entity-type data-entity-uuid data-entity-id data-view-mode data-entity-embed-display data-entity-embed-settings>``` to the 'Allowed HTML tags' setting. (Will happen automatically after https://www.drupal.org/node/2554687.)

## Usage

* For example, create a new *Article* content.
* Click on the 'E' button in the text editor.
* Enter part of the title of the entity you're looking for and select one of the search results.
* For **Display as**, choose one of the following options:
  * Rendered Entity
  * Entity ID
  * Label
* If chosen **Rendered Entity**, choose one of the following options for **View mode**:
  * Default
  * Full content
  * RSS
  * Search index
  * Search result highlighting input
* Optionally, choose to align left, center or right.

## Embedding entities without WYSIWYG

Users should be embedding entities using the CKEditor WYSIWYG button as described above. This section is more technical about the HTML markup that is used to embed the actual entity.

### Embed by UUID (recommended):
```html
<drupal-entity data-entity-type="node" data-entity-uuid="07bf3a2e-1941-4a44-9b02-2d1d7a41ec0e" data-entity-embed-display="entity_reference:entity_reference_entity_view" data-entity-embed-settings='{"view_mode":"teaser"}' />
```

### Embed by ID (not recommended):
```html
<drupal-entity data-entity-type="node" data-entity-id="1" data-entity-embed-display="entity_reference:entity_reference_entity_view" data-entity-embed-settings='{"view_mode":"teaser"}' />
```

### Entity Embed Display Plugins

Embedding entities uses an Entity Embed Display plugin, provided in the `data-entity-embed-display` attribute. By default we provide four different Entity Embed Display plugins out of the box:

- entity_reference:_formatter_id_: Renders the entity using a specific Entity Reference field formatter.
- entity_reference:_entity_reference_label_: Renders the entity using the "Label" formatter.
- file:_formatter_id_: Renders the entity using a specific File field formatter. This will only work if the entity is a file entity type.
- image:_formatter_id_: Renders the entity using a specific Image field formatter. This will only work if the entity is a file entity type, and the file is an image.

Configuration for the Entity Embed Display plugin can be provided by using a `data-entity-embed-settings` attribute, which contains a JSON-encoded array value. Note that care must be used to use single quotes around the attribute value since JSON-encoded arrays typically contain double quotes.

The above examples render the entity using the _entity_reference_entity_view_ formatter from the Entity Reference module, using the _teaser_ view mode.

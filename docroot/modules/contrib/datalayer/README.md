Data Layer
==============
**Get page meta data from inside Drupal out to the client-side.**

This Drupal module outputs various CMS page meta data (like content type, author uid, taxonomy terms), which can be used for all kinds of front-end features. This works for all entity types and is easy to extend with hooks.

The phase "data layer" is a Google term, but it's a great standard for your server to setup a foundation for the front-end. It's generic enough that other services managed in GTM can use application data, also you can use this data on your site to implement great client-side features, like anonymous user tracking, etc.

This module was created to work with <a href="https://www.drupal.org/project/semi_anonymous">Semi Anonymous</a>, but is more widely useful.

**Issues:** Post problems or feature requests to the [Drupal project issue queue](https://drupal.org/project/issues/datalayer).

## Meta data output
It's critial to have easy and reliable JS access to the meta data about the pages of your site. This modules helps output that info. Yes, you could get some of this from the DOM, but that's messy. Configure what gets pushed out via the admin page. This includes global control over all entity properties. You can also control if taxonomy should be inluded, and which vocabularies should be exposed. Here's _some_ of what's available by default...
```json
{
  "drupalLanguage": "en",
  "userStatus": "anonymous",
  "userUid": "555",
  "entityId" : "123",
  "entityLabel" : "My Cool Page",
  "entityType" : "node",
  "entityBundle" : "article",
  "entityUid" : "555",
  "entityLanguage" : "en",
  "entityTaxonomy" : {
    "special_category" : {
      "25" : "Term Name",
      "26" : "Another Term"
    },
    "my_type" : {
      "13" : "Some Tag",
      "14" : "Another Tag"
    }
  }
}
```

## Adding to the data layer

### Suggest entity properties
You can easily suggest additional entity properties to the Data Layer module by using the `hook_datalayer_meta()` function. Example:
```php
function my_module_datalayer_meta() {
  return array(
    'property',
  );
}
```
It will be added to the page as:
```json
{
  "entityProperty": "whatever the value is"
}
```

### Add data layer values
In order to easily add data layer properties and values on the fly within your code, use the `datalayer_add()` function much like you would `drupal_add_js` or `drupal_add_css`.
NOTE: In that case of matching keys, any added property/value pairs can overwrite those already available via normal entity output. You _should_ be using the `datalayer_alter()` function if that's the intent, as added properties are available there.
Example:
```php
function _my_module_myevent_func($argument = FALSE) {
  if ($argument) {
    datalayer_add(array(
      'drupalMyProperty' => $argument,
      'userAnotherProperty' => _my_module_other_funct($argument),
    ));
  }
}
```

## Alter output

### Alter available properties
You can also alter what entity properties are available within the admin UI (add candidates) via the `hook_datalayer_meta_alter()` function. You may want to take advantage of the entity agnostic menu object loader function found within the module. For example you might want to hide author information in some special cases...
```php
function my_module_datalayer_meta_alter(&$properties) {
  // Override module norm in all cases.
  unset($properties['entityUid']);

  // Specific situation alteration...
  $type = false;
  if ($obj = _datalayer_menu_get_any_object($type)) {
    if ($type === 'node' && in_array(array('my_bundle', 'my_nodetype'), $obj->type)) {
      // Remove author names on some content type.
      if ($key = array_search('name', $properties)) {
        unset($properties[$key]);
      }
    }
    elseif ($type === 'my_entity_type') {
      // Remove some property on some entity type.
      if ($key = array_search('my_property', $properties)) {
        unset($properties[$key]);
      }
    }
  }
}
```

### Alter output values
You can also directly alter output bound data with the `hook_datalayer_alter()` function. Use this to alter values found in normal entity output or added by `datalayer_add()` within the same or other modules, to support good architecture.
```php
function my_module_datalayer_alter(&$data_layer) {
  // Make the title lowercase on some node type.
  if (isset($data_layer['entityBundle']) && $data_layer['entityBundle'] == 'mytype') {
    $data_layer['entityLabel'] = strtolower($data_layer['entityLabel']);
  }
}
```

## Use the data layer client-side
There are lots of great client-side uses for your pages' data. The `dataLayer` object is used as a warehouse for Google Analytics and GTM, and is therefor an array of objects. To safely access properties you should use the <a href="#data-layer-helper">data-layer-helper</a> library, a dependency of this module.
You might act on this info like this...
```javascript
var myHelper = new DataLayerHelper(dataLayer),
    myVocab = myHelper.get('entityTaxonomy.my_category'),
    specialTagTid = 25;

// Check for some term tag bring present.
if (typeof myVocab !== 'undefined' && myVocab.hasOwnProperty(specialTagTid)) {
  doMyThing(myHelper.get('entityUid'), myHelper.get('drupalLanguage'), myHelper.get('entityLabel'));
}
```

## Language utilities
If your project is multilingual this module provides several useful client-side tools...
```
Drupal.settings.dataLayer.languages;
```
Returns objects of your active langauges with full-details such as: prefix, native text, enabled, domain, name, etc.

```
Drupal.behaviors.dataLayer.langPrefixes();
```
Returns an array of your active language prefix codes, excluding any taht are left empty.

## Dynamic additions
You can add new data to the data layer dynamically. This is how GA does it, you should follow those patterns.
```javascript
// Inform of link clicks.
$(".my-links").click(function() {
  dataLayer.push({ 'eventLinkClick': $(this).text() });
});

// Inform of Views filter changes.
$(".views-widget select.form-select").change(function() {
  dataLayer.push({
    'eventFilterSet': $(this).closest('.view').attr('id') + ':' +
      $(this).attr('name') + ':' + $(this).find("option:selected").text();
  });
});
```

## Google
Chances are you're interested in this module to get data from Drupal into the data layer to pass on to Google Tag Manager.
To do this just check the box on the admin screen. If you want to more about working with Google services, refer to the [Tag Manager - Dev Guide](https://developers.google.com/tag-manager/devguide).

### Data Layer Helper
To employ more complex interactions with the data you may want load the [data-layer-helper](https://github.com/google/data-layer-helper) library. It provides the ability to "process messages passed onto a dataLayer queue," meaning listen to data provided to the data layer dynamicly.
To use, add the compiled source to the standard Drupal location of `sites/all/libraries/data-layer-helper/data-layer-helper.js` and check the box on the admin page to include it.

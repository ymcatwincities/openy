See also the Data Layer module readme datalayer/README.md

The Data Layers module output data on the page in a json format. By default it will output properties (langcode, vid, name, uid, created, status, roles) and related taxonomy for any node, user, or any route based entity.

A limited set of properties are available via the Data Layers configuration form at /admin/config/search/datalayer (langcode, vid, name, uid, created, status, roles).

Adding additional properties can be done through use of hook_datalayer_meta().
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

Altering which properties will be output can be done via hook_datalayer_meta_alter().
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

Adding additional data can be done using datalayer_add().
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

To alter the data to be output use hook_datalayer_alter().
```php
function my_module_datalayer_alter(&$data_layer) {
  // Make the title lowercase on some node type.
  if (isset($data_layer['entityBundle']) && $data_layer['entityBundle'] == 'mytype') {
    $data_layer['entityLabel'] = strtolower($data_layer['entityLabel']);
  }
}
```
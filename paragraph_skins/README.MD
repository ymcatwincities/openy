### Paragraph Skins
This module implements custom styles for the paragraph bundle by adding select fields with custom libraries.
Frontend developer uses Paragraph Skins to implement various CSS or js library for one paragraph type.

### How to use:
You can add in your custom module hook_paragraph_skins, and override skins data.
The library with your styles should be implemented in your module how drupal library.
In CSS library you can select paragraph by HTML class: `paragraph_type-skin-label`,
generated from paragraph machine name in lower register and skin label in lower register and replace space by a hyphen.

##### Explaining for data:
Data structure is the standard php array.
```php
$data = [
 'paragraph_type' =>
     'Skin Label' =>
       'library' => 'module_name/library_name',
        // Theme name for attaching library when theme active
        // and paragraph skin set to default.
        // optional
       'theme' => 'theme_name'
   ];
```
##### Example:
```php
function my_custom_module_paragraph_skins(array &$data) {
  // Override styles for my banner.
  $data['my_banner'] = [
    'My cool skin' => ['library' => 'my_custom_module/cool_library'],
    'Color skin' => ['library' => 'my_custom_module/color_banner'],
  ];
  // Add style to my slider.
  $data['my_slider']['My slider skin'] = [
      'library' => 'my_custom_module/slider',
      'theme' => 'batric'
  ]
}
```
##### Explaining for preprocess:
@TODO

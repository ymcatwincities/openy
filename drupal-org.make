core = 8.x
api = 2
defaults[projects][subdir] = contrib

projects[features] = 3.2
projects[config_update] = 1.3
projects[entity] = 1.0-alpha4
projects[datalayer] = 1.x-dev
projects[media_entity] = 1.6
projects[media_entity_image] = 1.2
projects[media_entity_document] = 1.1
projects[address] = 1.0-rc3
projects[paragraphs] = 1.0
projects[entity_reference_revisions] = 1.2
projects[pathauto] = 1.0-rc1
projects[ctools] = 3.0-alpha27
projects[token] = 1.0-rc1
projects[field_group] = 1.0-rc6
projects[video_embed_field] = 1.4
projects[jquery_colorpicker] = 1.3
projects[entity_browser][subdir] = contrib
projects[entity_browser][version] = 1.0-rc1
projects[entity_browser][patch][] = "https://www.drupal.org/files/issues/2845037_15.patch"
projects[dropzonejs] = 1.0-alpha3
projects[inline_entity_form] = 1.0-beta1
projects[embed] = 1.0-rc3
projects[entity_embed] = 1.0-beta2
projects[views_block_filter_block][subdir] = contrib
projects[views_block_filter_block][version] = 1.x-dev
projects[views_block_filter_block][patch][] = "https://www.drupal.org/files/issues/empty_exposed_forms-2790505-10.patch"
projects[plugin] = 2.4
projects[libraries] = 3.x-dev
projects[migrate_plus] = 3.0-beta1
projects[migrate_tools] = 3.0-beta1
projects[optimizely] = 1.2
projects[verf] = 1.0-beta6
projects[simple_menu_icons][subdir] = contrib
projects[simple_menu_icons][version] = 1.x-dev
projects[simple_menu_icons][patch][] = "https://www.drupal.org/files/issues/clear-cache-after-simple_menu_icons_css_generate-2847964.patch"
projects[views_infinite_scroll] = 1.3
projects[slick] = 1.0-rc1
projects[slick_views] = 1.0-rc2
projects[blazy] = 1.0-rc1
projects[geolocation] = 1.9
projects[google_tag] : 1.x-dev
projects[confi][subdir] = contrib
projects[confi][version] = 1.3
projects[confi][patch][] = "https://www.drupal.org/files/issues/confi-drush-call-hooks-from-disabled-2856910.patch"

libraries[dropzone][type] = library
libraries[dropzone][download][type] = get
libraries[dropzone][download][url] = https://github.com/enyo/dropzone/archive/v4.3.0.zip
libraries[dropzone][destination] = libraries
libraries[jquery_colorpicker][type] = library
libraries[jquery_colorpicker][download][type] = get
libraries[jquery_colorpicker][download][url] = http://www.eyecon.ro/colorpicker/colorpicker.zip
libraries[jquery_colorpicker][destination] = libraries
libraries[slick][type] = library
libraries[slick][download][type] = get
libraries[slick][download][url] = https://github.com/kenwheeler/slick/archive/1.6.0.zip
libraries[slick][destination] = libraries
libraries[blazy][type] = library
libraries[blazy][download][type] = get
libraries[blazy][download][url] = https://github.com/dinbror/blazy/archive/1.8.2.zip
libraries[blazy][destination] = libraries
libraries[jquery.easing][type] = library
libraries[jquery.easing][download][type] = get
libraries[jquery.easing][download][url] = https://github.com/gdsmith/jquery.easing/archive/1.4.1.zip
libraries[jquery.easing][destination] = libraries

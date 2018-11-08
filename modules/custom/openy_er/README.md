### Open Y Entity Reference Tweaks module.

The idea of the module is to provide enhancements to the Core entity reference fields in Open Y installation profile purposes.

#### Entity Reference Selection Handler plugins

The plugins stored at `src/Plugin/EntityReferenceSelection` must be used in ER-fields if additional bundle filtering is setup and you export configuration of those fields into the profile.
Default Selection Handlers put dependency for each single bundle setup there, because the core works the way, that 'target_bundles' field settings entries add dependencies. See [EntityReferenceItem::calculateDependencies()](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21Plugin%21Field%21FieldType%21EntityReferenceItem.php/function/EntityReferenceItem%3A%3AcalculateDependencies/8.5.x)

`DefaultSelectionNoDependency` is basically needed to create a label for the group of Open Y selection handlers.

`SelectionNoDependecyTrait` is useful for inheriting from existing entity type specific selection handlers (see NodeSelectionNoDependency implementation as example).
See [Traits Precendence](http://php.net/manual/en/language.oop5.traits.php#language.oop5.traits.precedence) section to understand how PHP handles method overrides when traits are used.

#### Migrating from core selection handlers to Open Y selection handlers

- Go to the required entity refrence field configuration page that is provided by Field UI
- Find "REFERENCE TYPE" section that contains "Reference method" field and a set of checkboxes for bundles limiting
- Memorize the set of the selected bundles
- Update "Reference method" value from 'Default' to 'Default (Open Y)'
- Restore the state of the checkboxes (it can be named 'Content types' for nodes, or just 'Bundles' for other entity types)
- Submit the form
- Export the config (or the whole feature)
- Verify field config doesn't contain dependencies to the bundle configs
- Make sure the module now depends on `openy_er` module


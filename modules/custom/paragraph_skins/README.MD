### Paragraph Skins
This module implements custom styles for the paragraph bundle by adding select fields with custom libraries.
Frontend developer uses Paragraph Skins to implement various CSS or js library for one paragraph type.

### How to use:
If you want to add additional skins, please create a new file or add new items to the
<yourmodule>.paragraph_skins.yml file and clear cache.
After this action, new skins will appear at the system.

##### Explaining for data:
Data structure is the standard array in the YAML file.
```yaml
paragraph_skins:
  - name: feature-card // Skin key
    label: 'Feature card'
    paragraph_type: 1c // Paragraph bundle to which we want attach this skin
    library: 'openy_prgf_1c/feature-card' // Library for skin
    theme_library:
      openy_carnation: 'openy_carnation/feature-card' // Theme name and library name
    group: general // Skin group
```

##### Explaining for preprocess:

At paragraph edit form, user is able to select additional skin for paragraph entity.
Select list contains only skins, which are prepared for this paragraph bundle.

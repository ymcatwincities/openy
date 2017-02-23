# How to support upgrade path
All changes in configurations should be added to appropriate hook\_update\_N in order to update already existing environments.

### `openy.install` in profile
In this file we should put updates that are related to the distribution in general and don't fit into any feature.

- Enable/Disable module
- General configs

### `openy_*.install` in modules
In case if you update some configuration for specific feature, make sure that you put updates into appropriate module.


### Modules version
Whenever you change configuration in module, you should increase module version.

Before:
```
version: 8.x-1.0
```

After:
```
version: 8.x-1.1
```



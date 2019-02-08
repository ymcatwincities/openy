### OpenY PEF GXP Sync

Synchronizes Groupex schedules to PEF.

### Quick start

#### Configure OpenY GXP module

Go to `/admin/openy/integrations/groupex-pro/gxp`.

1. Set up your GroupExPro client id.
2. Provide parent activity ID. Should be Group Exercises under Fitness.

#### Setup production mode

By default the module operates in demo mode.
While synchronisation in demo mode syncer proceeds the only first 5 class items for each found location.

In order to switch production mode on set the next config:

    \Drupal::configFactory()->getEditable('openy_pef_gxp_sync.settings')->set('is_production', TRUE)->save(TRUE);

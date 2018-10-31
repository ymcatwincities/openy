### YGTC PEF GXP Sync

Synchronizes Groupex schedules to PEF for YGTC.

### Quick start

By default the module operates in debug mode.
In order to switch production mode on set the next config:

    \Drupal::configFactory()->getEditable('ygtc_pef_gxp_sync.settings')->set('is_production', TRUE)->save(TRUE);

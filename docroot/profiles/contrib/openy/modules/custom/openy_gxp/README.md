### Open Y Daxko integration. 

Uses APIv2 https://api.daxko.com/v3/docs/api/index.html

 - Imports Daxko Categories into Open Y Classes.
 - Imports Daxko Offerings into Open Y Sessions.

See Settings page at admin/openy/integrations/daxko/daxko2 that needs to be filled before running import.

Import can be run at admin/openy/integrations/daxko/daxko-import page.

Module uses one API call to get all the Daxko offerings, saves it into two CSV files
and then run Drupal migrations (migrate_plus.migration.daxko_categories_import and
migrate_plus.migration.daxko_offerings_import).

See openy_daxko2_example for more details on how you will implement this module for actual client.

### QuickStart

See [video intoduction](https://www.youtube.com/watch?v=1SHlxMpciUY) prepared by developers.

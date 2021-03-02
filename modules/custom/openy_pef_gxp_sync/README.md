### OpenY PEF GXP Sync

Synchronizes GroupEx schedules to PEF.

### Quick start

#### Configure OpenY GXP module

Go to `/admin/openy/integrations/groupex-pro/gxp`.

1. Set up your GroupExPro client id.
2. Provide parent activity ID. Should be Group Exercises under Fitness.

#### How to sync my groupex data to my project?

Run `drush openy-pef-gxp-sync` command from your project docroot.

### How the syncer works

The syncer consists of the next steps:

  1. Fetcher - fetches data from GroupEx API.
  2. Wrapper - processes the data for saving (maps location ids, fixes title encoding problems, etc).
  3. Wrapper - groups all items by Class ID and Location ID, calculates hashes.
  4. Wrapper - prepares data to be removed (extra items in DB or changed hashes)
  5. Wrapper - prepares data to be created (new items + changed hashes)
  6. Cleaner - removes data to be removed.
  7. Saver   - creates data to be created.

### How the syncer works in details (for developers)

#### Adding & Removing locations.

1. If a location is removed in API it should be removed in DB.
2. If a location is added in API it should be added (with classes) in DB.
3. If a class is removed in API it should be removed in DB (with all class items);
3. If a class is added in API it should be added in DB (with all class items);

#### Updating classes.

1. Each GroupEx class can have several class items (with the same class ID).
2. We compare hashes for Location ID + Class ID + all class items inside (on unprocessed data!).
3. If hash is changed we should remove all items belongs to this hash and create them again.

### How to debug

1. To emulate API data please use `FetcherDebuggerClass`. Just replace `@openy_pef_gxp_sync.fetcher` with
`@openy_pef_gxp_sync.fetcher_debugger` to emulate API response.

2. Use `DEBUG_MODE` constants inside classes to debug specific service.

### Known issues in sync.

1. There is an issue if class in Groupex has category set to "General" - it will not be synced and displayed at PEF.

### Default Syncer behavior

By default Syncer creates unpublished Session nodes.
In order for them to become visible in Schedules application you'd need to set config variables to allow unpublished entities to be displayed

- config `openy_repeat.settings` - variable `allow_unpublished_references: 1` - this is for unpublished Session, Program, Program Subcategory session nodes.
- config `openy_session_instance.settings` - variable `allow_unpublished_references: 1` - this works only for unpublished Session nodes.

At this moment we have no UI for setting these variables, so using `drush cset` or importing configs via Config Manager is recommended.


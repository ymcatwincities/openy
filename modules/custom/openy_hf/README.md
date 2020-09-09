# Open Y Headerless Footerless HTML templating

## Usage

Enable this module as you would any other via the `Extend` page or with `drush`.

### To disable the header or footer

Pass the `dnr` query string to the page with `h` and/or `f`.

- `https://example.com/?dnr=h` - Disable the header section
- `https://example.com/?dnr=f` - Disable the footer section
- `https://example.com/?dnr=hf` - Disable the header and footer section

### To render ONLY the header or footer

This module exposes `/header.html` and `/footer.html` routes. Those can be
accessed by any application to render only the header or footer.

### Modifying Configuration

Configuration is set when this module is installed
(see [config/install/openy_hf.settings.yml]) but there is currently no UI.
To modify the preset configuration you can use `drush config-set`
([docs](https://drushcommands.com/drush-8x/config/config-set/)).

For example:
- `drush cset openy_hf.settings header_replacements.selector ".page-middle .lead-copy"`
- `drush cset openy_hf.settings footer_replacements.selector "#page-footer"`

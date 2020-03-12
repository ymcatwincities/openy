# Google Custom Search configuration

The Open Y release **8.2.4** introduces Google Custom Search for the website out of the box.

## Enabling the module

### Fresh installations
The search feature is included in the `Extended` installation type.
For `Standard` see the <a href="#existing-websites">Existing websites</a> section.

If you are installing a fresh Open Y website and going through the installation process via the Web interface, on the 3rd party integration step you can specify Google Search ID. If you specify the Google Search ID in this form your site's search feature is up.

### Existing websites
The search feature is not automatically enabled after upgrading an Open Y website. You have to manually enable it.

In order to do that:
1. Log in as an admin (or a user with the `administrator` role).
1. Go to the Open Y package install page (Admin menu > Open Y > Extend > Install, or `/admin/openy/extend/list`)
1. Find the `SEARCH` package there, tick the checkbox and submit the form.

Now the search modules are enabled and the header of the website should have a search field. 
Upon installation, the modules create a Landing page for search results and point the header search form to the page.

## Configuring the Google Search modules
1. Go to the Google Search settings form (Admin menu > Open Y > Settings > Google Search settings, or `/admin/openy/settings/google-search`).
1. Set the value of the `Google Search ID` field (see the following <a href="#obtaining-search-engine-id">section</a> for details) and submit the form.

## Obtaining Search Engine ID

1. Go to https://cse.google.com/, register if you haven't yet, log in if you aren't logged in.
1. Create the Search Engine (this process is explained in Google documentation https://support.google.com/customsearch/answer/4513882?hl=en&ref_topic=4513742):
   1. Click "New Search Engine".
   1. Specify the domain of your website (e.g. `www.openymca.org`).
   1. Specify the name of the Search Engine (e.g. `openymca.org`).
   1. Click "Create".
1. On the newly created Search Engine page there is the `Search engine ID` field. Use this value in the Open Y Google Search configuration form.

## Configuring the Search Engine look and feel

1. Go to `Look and feel` section of the Search Engine
1. In the `Layout` tab, select `Full width` option and click `Save`

If this change hasn't made, the search results on the website are shown in a popup window.

## Dealing with ads

By default, newly created Search engines use Free Edition (with ads) of the service. As YMCAs are non-profit organizations they have the option to switch to Non-profit Edition of the CSE, where it is possible to disable ads. 

Take a look here https://support.google.com/customsearch/answer/4542102?hl=en&ctx=topic

If you are already registered as a Non-profit in Google:
1. From the [CSE Control Panel](https://cse.google.com), select the search engine you want to change.
1. Click **Setup** then **Make Money**
1. Toggle the **Show Ads** option to off. 

## Advanced setup

Official Google documentation https://support.google.com/customsearch/topic/4542213?hl=en&ref_topic=4513868

### Mutli-site search

You can add not only your website's domain but other domains as well if you have other websites dedicated to your Association but split from the main website.

You can also add not only the whole websites but their parts by specifying patterns like `example.com/blog/*`.

### Refinements and facets

https://support.google.com/customsearch/answer/4542637?hl=en&ctx=topic&topic=2642564&visit_id=637166170019174137-3540762397&rd=1

Refinements let users filter results according to categories you provide.

Refinements appear in the search results page as tabs. The content of each tab is configured in Search features > Refinement section of the Custom Search Control panel.

To set up a dedicated tab in search results for Blog posts do the following:
1. In Control panel, go to `Search features` > `Refinements`
1. Click `Add`
   1. Set the name of the refinement to `Blog`
   1. Select `Search only the sites with this label` for `How to search sites with this label?`
   1. Click `Ok`
1. Go to `Setup` 
1. Find `Sites to search`, click `Add`
   1. Add the `yourymcadomain.org/blog/*` in the text field
   1. Select `Blog` in the Label dropdown
   1. Select `Include just this specific page or URL pattern I have entered`
   1. Click `Save`

The search results page now shows the `Blog` tab that only shows blog entries relevant to the search term.

### Promotions

Official Google documentation https://support.google.com/customsearch/answer/4542640?hl=en&ref_topic=4542213

## Information for developers

[Google Custom Search Developers documentation ](https://developers.google.com/custom-search/docs/overview)

### Enabling via Drush

Use the following snippet to enable the package on existing websites:
```
drush en openy_google_search
```

### Configuring the module via Drush

Use the following snippet when you install Open Y via Drush to set the Search Engine ID:
```
drush site-install openy \
   --account-pass=password \
   --db-url="mysql://user:pass@host:3306/db" \
   --root=/var/www/docroot \
   openy_configure_profile.preset=extended \
   openy_theme_select.theme=openy_rose \
   openy_third_party_services.google_search_engine_id="01234567890123456789:abcedefgh"
```

The `openy_third_party_services.google_search_engine_id` parameter sets the Search Engine ID (`01234567890123456789:abcedefgh` in the example).

Use the following snippet to set the Search Engine ID on already installed websites:
```
drush config-set openy_google_search.settings google_engine_id "01234567890123456789:abcedefgh"
```

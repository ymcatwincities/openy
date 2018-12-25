# YMCA Training Reports

How to run Proof of Concept:

1. Open /devel/php
2. Run following command
```php
To run reports
\Drupal::service('yptf_kronos_reports.generate')->generateReports();

For debug use: 
 - 'dpm' to print reports on page;
 - 'cachedpm' to print on page and use cache
 - 'cacheemailyour@mail.com' to use cache and send reports to 'your@mail.com' (type mail direct after 'email' or 'cacheemail') 
Ex.:
\Drupal::configFactory()->getEditable('yptf_kronos.settings')->set('debug', 'dpm')->save(TRUE);
\Drupal::service('yptf_kronos_reports.generate')->generateReports();

```
3. Make sure you see message with statistics per trainer and per location.

### TODO

 - Move `calculateReports()` to base class - it's identical.

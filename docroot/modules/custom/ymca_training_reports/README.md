# YMCA Training Reports

How to run Proof of Concept:

1. Open /devel/php
2. Run following command
```php
$service = \Drupal::service('ymca_training_reports.poc');
$service->poc();
```
3. Make sure you see message with statistics per trainer and per location.
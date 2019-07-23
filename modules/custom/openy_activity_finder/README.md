# Examples

### How to override processResults in ActivityFinder

See `openy_activity_finder.api.php`
```php
/**
 * Implements hook_activity_finder_program_process_results_alter().
 */
function custom_module_activity_finder_program_process_results_alter(&$data, NodeInterface $entity) {
  // Get formatted session data from some custom service.
  $formatted_session = \Drupal::service('ymca_class_page.data_provider')
    ->formatSessions([$entity], FALSE);
  $formatted_session = reset($formatted_session);

  // Fix pricing according to YMCA price customization.
  $data['price'] = '';
  if (!empty($formatted_session['prices'])) {
    foreach ($formatted_session['prices'] as $price) {
      $data['price'] .= implode(' ', $price) . '<br>';
    }
  }

  // Fix availability and registration according to YMCA customization.
  $messages = [
    'begun' => t('This class has begun.'),
    'will_open' => t('Registration for this class opens shortly. Please check back.'),
    'inperson' => t('Online registration is closed. Visit a YMCA branch to register.'),
    'included_in_membership' => t('Included in Membership'),
  ];

  if (isset($messages[$formatted_session['reg_state']])) {
    $data['availability_note'] = $messages[$formatted_session['reg_state']];
  }
}
```
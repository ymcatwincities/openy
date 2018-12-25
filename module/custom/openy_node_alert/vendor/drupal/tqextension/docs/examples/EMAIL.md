### Testing sending letters

Scenarios, which will use steps for testing emails, should be tagged
with `@email` tag. If you want to use IMAP, then `@imap` tag should be added.

```gherkin
@email
Scenario: Create an account
  Given I am logged in as a user with the "administrator" role
  Then I am at "user/create"
  ...
  And I press the "Create" button
  Then I should see no errors
  And login with credentials that was sent on "test@ffwagency.com"
```

**IMPORTANT**: For login with credentials that have been sent via email you should correctly configure your Behat.

Example of `mail_account_strings()`:
```php
/**
 * The part of message with credentials that will be sent after registration.
 *
 * WARNING! This function is needed for correct translate of this part and
 * for usage in Behat testing. In "hook_mail()" this function should be
 * called with username and password as parameters and in testing - with
 * regexp for parse credentials.
 *
 * @param string $name
 *   User login or regexp to parse it.
 * @param string $pass
 *   User password or regexp to parse it.
 *
 * @return array
 *   An associative array with translatable strings.
 */
function mail_account_strings($name, $pass) {
  return array(
    'username' => t('Username: !mail', array('!mail' => $name)),
    'password' => t('Password: !pass', array('!pass' => $pass)),
  );
}
```

The `mail_account_strings()` function always must return an array with two keys: "username" and "password". The value of each key - should be a string returned by `t()` function. Text of string can be any, but it definitely should have the placeholder that can be replaced by one of the credentials in `hook_mail()` and by regexp - in Behat method.

Example of `hook_mail()`:
```php
/**
 * Implements hook_mail().
 */
function hook_mail($key, &$message, $params) {
  switch ($key) {
    case 'account':
      $message['subject'] = t('User account');
      $message['body'][] = t('You can login on the site using next credentials:');
      $message['body'] += mail_account_strings($params['mail'], $params['pass']);
      break;
  }
}
```


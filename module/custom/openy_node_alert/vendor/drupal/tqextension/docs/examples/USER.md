# Work with Drupal users

Scenarios, which will use steps for testing WYSIWYG editors, should be tagged with `@user` tag.

```gherkin
Scenario: Login as a user with filled fields
  Given I am logged in as a user with "administrator" role and filled fields:
    | Full name | Sergii Bondarenko   |
    | Position  | Developer           |
    | Company   | TestCompany         |
```

# Attach an image via CKFinder

```gherkin
@javascript
Scenario: Attach an image via CKFinder
  Given I am logged in as a user with "administrator" role
  Then I am on the "node/add/article" page
  When I press on "Ckfinder browser"
  Then I switch to CKFinder window
  And press on "People"
  When I double click on "Anna.png"
  Then I should see the thumbnail
```

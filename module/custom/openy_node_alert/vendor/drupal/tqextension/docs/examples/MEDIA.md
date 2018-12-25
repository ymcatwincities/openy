# Upload an image with help of the Media module

File **logo.jpg** must be located in `resources` directory.

## Example

```gherkin
@javascript
Scenario: Testing media
  Given I press on "Select media"
  Then I switch to an iframe "mediaBrowser"
  And I attach file "logo.jpg" to "edit-upload"
  And I press on "Submit"
  Then I switch back from an iframe
  And I should see the thumbnail
  And I should see "Remove media"
```

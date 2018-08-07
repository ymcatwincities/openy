# Testing WYSIWYG

Scenarios, which will use steps for testing WYSIWYG editors, MUST be tagged with `@wysiwyg` tag. Additionally
the `@wysiwyg:VENDOR_NAME` tag must be added  and `VENDOR_NAME` must be the same as an object name that implement
WYSIWYG API for TqExtension.

## Supported editors (out of the box)

- CKEditor
- EpicEditor
- jWysiwyg
- MarkItUp
- TinyMCE

**Note**: Only one editor could be tested per scenario.

```gherkin
@wysiwyg @wysiwyg:CKEditor
Scenario: Testing WYSIWYG
  Given I am logged in as a user with "administrator" role
  Then I am on the "node/add/employer" page
  And work with "Career" WYSIWYG editor
  And fill "<strong>Additional</strong>" in WYSIWYG editor
  And type " information" in WYSIWYG editor
  And should see "information" in WYSIWYG editor
  And should not see "vulnerability" in WYSIWYG editor
  Then I work with "Experience" WYSIWYG editor
  And fill "<strong>My awesome experience</strong><ul><li>Knowledge</li></ul>" in WYSIWYG editor
  And should see "awesome" in WYSIWYG editor
  Then fill "<p>text</p>" in "Education" WYSIWYG editor
```

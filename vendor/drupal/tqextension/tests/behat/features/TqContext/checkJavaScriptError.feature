@api @debug
Feature: Tq Context
  @javascript
  Scenario: Test "checkJavaScriptError" method
    Given I am on the "tqextension/js-errors" page
    Then check that "TypeError: console.l0g is not a function" JS error appears on the page

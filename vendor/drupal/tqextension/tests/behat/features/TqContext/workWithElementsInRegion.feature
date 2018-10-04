@api @debug
Feature: Tq Context
  Scenario: Test "workWithElementsInRegion" method
    Given I am on the "/" page
    And work with elements in "head" region
    Then I should see the "link" element with "rel" attribute having "shortcut icon" value
    Then I checkout to whole page
    And work with elements in "#header" region
    Then I should see the "a" element with "rel" attribute having "home" value

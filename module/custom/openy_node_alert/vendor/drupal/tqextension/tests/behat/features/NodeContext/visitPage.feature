@api @debug
Feature: Node Context
  Scenario: Test "visitPage" method
    Given "article" content:
      | title | status  |
      | A1    | 1       |
      | A2    | 1       |
    Then I am logged in with credentials:
      | username  | admin |
      | password  | admin |
    When I view the "A1" node of type "Article"
    Then I should see the text "A1"
    And edit current node
    And view the "A1" node

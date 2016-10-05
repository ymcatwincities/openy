Feature: Product on Healthy Living Page
  Background:
    Given I visit "/health__fitness/personal_training"

  @pt-products @fast-tests @headless
  Scenario: Personify products block on Healthy Living Page
    Then I should see "Purchase One-on-One Personal Training"

  @pt-products @fast-tests @headless
  Scenario Outline: Personify products on Healthy Living Page
    Then I should see "<product>"
    Examples:
      | product |
      | $45 for 1 |
      | $120 for 3 |
      | $225 for 6 |
      | $420 for 12 |
      | $540 for 20 |
      | $55 for 1 |
      | $150 for 3 |
      | $285 for 6 |
      | $540 for 12 |
      | $740 for 20 |
      | $70 for 1 |
      | $195 for 3 |
      | $375 for 6 |
      | $720 for 12 |
      | $999 for 20 |
      | $80 for 1 |
      | $225 for 3 |
      | $435 for 6 |
      | $840 for 12 |
      | $1199 for 20 |

  @pt-products @pt-products-popup @javascript
  Scenario Outline: Personify products popup
    When I click "<product>"
    And I wait 2 seconds
    Then I should see "Choose a location"
    Examples:
      | product |
      | $45 for 1 |
      | $120 for 3 |
      | $225 for 6 |
      | $420 for 12 |
      | $540 for 20 |
      | $55 for 1 |
      | $150 for 3 |
      | $285 for 6 |
      | $540 for 12 |
      | $740 for 20 |
      | $70 for 1 |
      | $195 for 3 |
      | $375 for 6 |
      | $720 for 12 |
      | $999 for 20 |
      | $80 for 1 |
      | $225 for 3 |
      | $435 for 6 |
      | $840 for 12 |
      | $1199 for 20 |

Feature: Products on Location Page
  Background:
    Given I visit "/locations/andover_ymca_community_center/health__fitness/personal_training"

  @pt-products @fast-tests @headless
  Scenario: Personify products block on Location Page
    Then I should see "Purchase One-on-One Personal Training"

  @pt-products @fast-tests @headless
  Scenario Outline: Personify products on Location Page
    Then I should see "<product>"
    Examples:
      | product |
      | $75 for 1 |
      | $280 for 4 |
      | $540 for 8 |
      | $780 for 12 |
      | $1099 for 20 |
      | $95 for 1 |
      | $360 for 4 |
      | $700 for 8 |
      | $1020 for 12 |
      | $1499 for 20 |
      | $50 for 1 |
      | $180 for 4 |
      | $340 for 8 |
      | $480 for 12 |
      | $640 for 20 |
      | $70 for 1 |
      | $260 for 4 |
      | $500 for 8 |
      | $720 for 12 |
      | $1040 for 20 |

  @pt-products @pt-products-redirect @fast-tests @headless
  Scenario Outline: Personify products redirect
    When I click "<product>"
    Then I should see "Sign In"
    Examples:
      | product |
      | $75 for 1 |
      | $280 for 4 |
      | $540 for 8 |
      | $780 for 12 |
      | $1099 for 20 |
      | $95 for 1 |
      | $360 for 4 |
      | $700 for 8 |
      | $1020 for 12 |
      | $1499 for 20 |
      | $50 for 1 |
      | $180 for 4 |
      | $340 for 8 |
      | $480 for 12 |
      | $640 for 20 |
      | $70 for 1 |
      | $260 for 4 |
      | $500 for 8 |
      | $720 for 12 |
      | $1040 for 20 |

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

  @pt-products @pt-products-redirect @fast-tests @headless
  Scenario Outline: Personify products redirect
    When I click "<product>"
    Then I should see "Sign In"
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

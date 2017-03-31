@openy @api @membershipcalc
Feature: Membership Calculator Paragraph
  Check that Membership Calculator paragraph output is working

  Background: Setup membership content
    Given I create large branch content:
      | KEY                                 | behat_branch  |
      | title                               | Behat branch  |
      | field_location_address:country_code | US            |
      | :address_line1                      | 14 Behat road |
      | :locality                           | Seattle       |
      | :administrative_area                | WA            |
      | :postal_code                        | 98101         |
      | field_location_coordinates:lat      | 47.293433     |
      | :lng                                | -122.238717   |
      | field_location_phone                | +1234567890   |
    And I create paragraph of type membership_info:
      | KEY               | field_mbrshp_location | field_mbrshp_join_fee | field_mbrshp_monthly_rate | field_mbrshp_link:uri | :title |
      | mem_in_adult_1    | behat_branch          | 100                   | 73                        | http://openymca.org   | Select |
      | mem_in_family_1_1 | behat_branch          | 100                   | 109                       | http://openymca.org   | Select |
      | mem_in_family_2_1 | behat_branch          | 125                   | 129                       | http://openymca.org   | Select |
    And I create media of type image:
      | KEY            | name         | field_media_image |
      | adult_image    | adult.jpg    | adult.jpg         |
      | family_1_image | family_1.jpg | family_1.jpg      |
      | family_2_image | family_2.jpg | family_2.jpg      |
    And I create membership content:
      | KEY          | title           | status | field_mbrshp_description:value                                | :format   | field_mbrshp_info | field_mbrshp_image |
      | mem_adult    | Behat: Adult    | 1      | <p>Adults (30-64)</p>                                         | full_html | mem_in_adult_1    | adult_image        |
      | mem_family_1 | Behat: Family 1 | 1      | <p>One adult plus dependents</p>                              | full_html | mem_in_family_1_1 | family_1_image     |
      | mem_family_2 | Behat: Family 2 | 0      | <p>Two adults in same&nbsp;household&nbsp;plus dependents</p> | full_html | mem_in_family_2_1 | family_2_image     |
    And I create paragraph of type openy_prgf_mbrshp_calc:
      | KEY                    |
      | openy_prgf_mbrshp_calc |
    And I create landing_page content:
      | KEY             | title                            | field_lp_layout | field_content          |
      | membership_page | Behat Membership calculator test | one_column      | openy_prgf_mbrshp_calc |
    And I am an anonymous user

  Scenario: Visit membership page and see calculator
    When I view node "membership_page"
    Then I should see "Find the Membership That’s Best For You"
    And I should see "Membership Type"
    And I should see "Primary Location"
    And I should see "Summary"
    And I should see "Behat: Adult"
    And I should see "Behat: Family 1"
    And I should not see "Behat: Family 2"

  @javascript
  Scenario: Step through membership calculator for Behat: Adult
    When I view node "membership_page"
    And I click "#membership-calc-wrapper .form-element-wrapper:contains('Behat: Adult') .btn:contains('select')" element
    Then I click "#membership-calc-wrapper input[value='Next']" element
    And I wait for AJAX to finish
    And I select the radio button "Behat branch"
    Then I click "#membership-calc-wrapper input[value='Next']" element
    And I wait for AJAX to finish
    And I should see "Your selected branch"
    And I should see "Behat branch"
    And I should see "14 Behat road"
    And I should see "98101 WA"
    And I should see "Behat: Adult"
    And I should see "$73.00/month"
    And I should see "+ $100.00 one time joining fee"
    Then I press the "Complete registration" button
    And The current URL is "http://openymca.org/"

  @javascript
  Scenario: Step through membership calculator for Behat: Family 1
    When I view node "membership_page"
    And I click "#membership-calc-wrapper .form-element-wrapper:contains('Behat: Family 1') .btn:contains('select')" element
    Then I click "#membership-calc-wrapper input[value='Next']" element
    And I wait for AJAX to finish
    And I select the radio button "Behat branch"
    Then I click "#membership-calc-wrapper input[value='Next']" element
    And I wait for AJAX to finish
    And I should see "Your selected branch"
    And I should see "Behat branch"
    And I should see "14 Behat road"
    And I should see "98101 WA"
    And I should see "Behat: Family 1"
    And I should see "$109.00/month"
    And I should see "+ $100.00 one time joining fee"
    Then I press the "Complete registration" button
    And The current URL is "http://openymca.org/"

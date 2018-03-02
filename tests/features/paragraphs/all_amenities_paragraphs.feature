@openy @api @javascript
Feature: All Amenities Paragraph
  Check All Amenities paragraph can be created and displayed

  Background: Create basic landing page
    Given I am logged in as a user with the "Editor" role
    Then I create taxonomy_term of type amenities:
      | KEY           | name              |
      | behat_amenity | Behat Amenity One |
      | behat_amenit2 | Behat Amenity Two |
    And I create large branch content:
      | title                               | ABRANCH 01      |
      | field_location_address:country_code | US              |
      | :address_line1                      | Main road 10    |
      | :locality                           | Seattle         |
      | :administrative_area                | WA              |
      | :postal_code                        | 98101           |
      | field_location_coordinates:lat      | 47.293433       |
      | :lng                                | -122.238717     |
      | field_location_phone                | +1234567890     |
      | field_location_amenities            | behat_amenity   |
    And I create large paragraph of type all_amenities:
      | KEY              | behat_all_amenities |
      | field_prgf_title | BEHAT ALL AMENITIES |
    And I create landing_page content:
      | KEY               | title                   | field_lp_layout | field_content       |
      | landing_amenities | Behat Landing Amenities | one_column      | behat_all_amenities |
    And I am an anonymous user

  Scenario: Test amenities page
    Given I view node "landing_amenities"
    Then I should see "BEHAT ALL AMENITIES"
    And I should see "ABRANCH 01"
    When I check the box "Behat Amenity One"
    And I wait for AJAX to finish
    Then I should see "ABRANCH 01"
    When I check the box "Behat Amenity One"
    And I wait for AJAX to finish
    And I check the box "Behat Amenity Two"
    And I wait for AJAX to finish
    Then I should not see "ABRANCH 01"
    When I uncheck the box "Behat Amenity One"
    And I wait for AJAX to finish
    And I check the box "Behat Amenity Two"
    And I wait for AJAX to finish
    Then I should not see "ABRANCH 01"

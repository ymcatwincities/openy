@api @openy @javascript
Feature: Amenities page
  As Admin I want to make sure that amenities page is OK
  Background: Add background content as needed
    Given I create taxonomy_term of type amenities:
      | KEY           | name               |
      | behat_amenity | Behat1             |
      | behat_amenit2 | Behat2             |
    Then I create large branch content:
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
    And I am an anonymous user

  Scenario: Test amenities page
    Given I am on "/amenities"
    And I should see "ABRANCH 01"
    When I check the box "Behat"
#    And I wait 60 seconds
    And I wait for AJAX to finish
    Then I should see "ABRANCH 01"
    When I check the box "Behat1"
    And I check the box "Behat2"
    And I wait for AJAX to finish
#    And I wait 60 seconds
    Then I should not see "ABRANCH 01"
    When I uncheck the box "Behat1"
    And I check the box "Behat2"
#    And I wait 60 seconds
    And I wait for AJAX to finish
    Then I should not see "ABRANCH 01"

@openy @api
Feature: Branch Content type
  As Admin I want to make sure that Branch content type is available with needed fields.

  Background: Branch test setup
    Given I am logged in as a user with the "Editor" role
    And I create taxonomy_term of type amenities:
      | KEY            | name             |
      | behat_amenity  | Behat Amenity 01 |
      | behat_amenity2 | Behat Amenity 02 |
      | behat_amenity3 | Behat Amenity 03 |
      | behat_amenity4 | Behat Amenity 04 |
    And I create media of type image:
      | KEY      | name     | field_media_image |
      | image_01 | Image 01 | image_01.png      |
    And I create large block_content of type branch_amenities:
      | KEY               | behat_branch_amenities       |
      | info              | BEHAT BRANCH AMENITIES       |
      | field_sb_link:uri | http://openymca.org          |
      | :title            | Behat amenities link 01      |
      | field_icon_class  | list                         |
      | field_sb_title    | BEHAT BRANCH AMENITIES TITLE |
    And I create large block_content of type simple_block:
      | KEY                 | behat_branch_simple_block_01 | behat_branch_simple_block_02 |
      | info                | BEHAT BRANCH SB 01           | BEHAT BRANCH SB 02           |
      | field_sb_link:uri   | http://openymca.org          | http://openymca.org          |
      | :title              | Behat sb link 01             | Behat sb link 02             |
      | field_icon_class    | info                         | user-plus                    |
      | field_sb_title      | BEHAT BRANCH SB TITLE 01     | BEHAT BRANCH SB TITLE 02     |
      | field_sb_body:value | BEHAT BRANCH SB BODY 01      | BEHAT BRANCH SB BODY 02      |
      | :format             | full_html                    | full_html                    |
    And I create large paragraph of type banner:
      | KEY                    | behat_banner_01      | behat_banner_02      | behat_banner_03      |
      | field_prgf_headline    | BEHAT BANNER 01      | BEHAT BANNER 02      | BEHAT BANNER 03      |
      | field_prgf_image       | image_01             | image_01             | image_01             |
      | field_prgf_description | BEHAT DESCRIPTION 01 | BEHAT DESCRIPTION 02 | BEHAT DESCRIPTION 03 |
      | field_prgf_link:uri    | http://openymca.org  | http://openymca.org  | http://openymca.org  |
      | :title                 | Read about OpenY     | Read about OpenY     | Read about OpenY     |
    And I create large paragraph of type 3c:
      | KEY                  | behat_paragraph_3c           |
      | field_prgf_title     | BEHAT 3C                     |
      | field_prgf_3c_left   | behat_branch_amenities       |
      | field_prgf_3c_center | behat_branch_simple_block_01 |
      | field_prgf_3c_right  | behat_branch_simple_block_02 |
    And I create large branch content:
      | KEY                                 | behat_branch                                                  |
      | title                               | BEHAT BRANCH                                                  |
      | field_location_address:country_code | US                                                            |
      | :address_line1                      | Main road 10                                                  |
      | :locality                           | Seattle                                                       |
      | :administrative_area                | WA                                                            |
      | :postal_code                        | 98101                                                         |
      | field_location_coordinates:lat      | 47.293433                                                     |
      | :lng                                | -122.238717                                                   |
      | field_location_phone                | +1234567890                                                   |
      | field_header_content                | behat_banner_01                                               |
      | field_content                       | behat_paragraph_3c, behat_banner_02                           |
      | field_bottom_content                | behat_banner_03                                               |
      | field_location_amenities            | behat_amenity, behat_amenity2, behat_amenity3, behat_amenity4 |

  Scenario: Create basic branch
    Given I go to "/node/add/branch"
    And I fill in "Title" with "Branch One"
    And I fill in the following:
      | Street address | Main road 10   |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
      | Latitude       | 47.293433      |
      | Longitude      | -122.238717    |
      | Phone          | +1234567890    |
    When I press "Save"
    Then I should see the message "Branch Branch One has been created."

  Scenario: I see appropriate content on branch
    Given I view node "behat_branch"
    Then I should see "BEHAT BRANCH"
    And I should see "Main road 10"
    And I should see "98101"
    And I should see "WA"
    And I should see "Seattle"
    And I should see "+1234567890"
    And I should see "BEHAT BANNER 01"
    And I should see "BEHAT DESCRIPTION 01"
    And I should see "BEHAT BANNER 02"
    And I should see "BEHAT DESCRIPTION 02"
    And I should see "BEHAT BANNER 03"
    And I should see "BEHAT DESCRIPTION 03"

  Scenario: I see amenities on branch
    Given I view node "behat_branch"   
    And I should see "Behat Amenity 01"
    And I should see "Behat Amenity 02"
    And I should see "Behat Amenity 03"
    And I should see "Behat Amenity 04"
    And I should see "BEHAT BRANCH AMENITIES TITLE"
    And I should see the link "Behat amenities link 01"
    And I should see a "i.fa-list" element
    And I should see "BEHAT BRANCH SB TITLE 01"
    And I should see "BEHAT BRANCH SB BODY 01"
    And I should see the link "Behat sb link 01"
    And I should see a "i.fa-info" element
    And I should see "BEHAT BRANCH SB TITLE 02"
    And I should see "BEHAT BRANCH SB BODY 02"
    And I should see the link "Behat sb link 02"
    And I should see a "i.fa-user-plus" element

  @javascript
  Scenario: I validate the preferred branch functionality
    Given I view node "behat_branch"
    Then I should see "Save as preferred branch"
    And I should not have the cookie "openy_preferred_branch"
    When I click "Save as preferred branch"
    And I should see "This is your preferred branch, remove as preferred branch"
    And I should have the cookie "openy_preferred_branch"
    And The cookie "openy_preferred_branch" has expiration 365 days from now
    And The cookie "openy_preferred_branch" httpOnly is "FALSE"
    And The cookie "openy_preferred_branch" secure is "FALSE"
    And The cookie "openy_preferred_branch" value is the id of "behat_branch"
    When I click "This is your preferred branch, remove as preferred branch"
    And I should see "Save as preferred branch"
    And I should not have the cookie "openy_preferred_branch"

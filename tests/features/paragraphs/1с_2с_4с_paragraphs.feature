@openy @api
Feature: 1C 2C 4C Paragraphs
  Check 1C 2C 4C paragraphs can be created and displayed

  Background: Create basic landing page
    Given I am logged in as a user with the "Editor" role
    Then I create large block_content of type simple_block:
      | KEY                 | behat_simple_block_01       | behat_simple_block_02       |
      | info                | BEHAT SIMPLE BLOCK 01       | BEHAT SIMPLE BLOCK 02       |
      | field_sb_link:uri   | http://openymca.org         | http://openymca.org         |
      | :title              | Behat sb link 01            | Behat sb link 02            |
      | field_icon_class    | info                        | user-plus                   |
      | field_sb_title      | BEHAT SIMPLE BLOCK TITLE 01 | BEHAT SIMPLE BLOCK TITLE 02 |
      | field_sb_body:value | BEHAT SIMPLE BLOCK BODY 01  | BEHAT SIMPLE BLOCK BODY 02  |
      | :format             | full_html                   | full_html                   |
    And I create large branch content:
      | KEY                                 | behat_branch_blog_01 |
      | title                               | BEHAT BRANCH BLOG 01 |
      | field_location_address:country_code | US                   |
      | :address_line1                      | Main road 10         |
      | :locality                           | Seattle              |
      | :administrative_area                | WA                   |
      | :postal_code                        | 98101                |
      | field_location_coordinates:lat      | 47.293433            |
      | :lng                                | -122.238717          |
      | field_location_phone                | +1234567890          |
    And I create blog content:
      | KEY              | title                  | field_blog_location  |
      | blog_flexible_01 | Behat Blog Flexible 01 | behat_branch_blog_01 |
      | blog_flexible_02 | Behat Blog Flexible 02 | behat_branch_blog_01 |
    And I create large block_content of type flexible_content:
      | KEY            | behat_flexible_block_01 | behat_flexible_block_02 |
      | info           | BEHAT FLEXIBLE BLOCK 01 | BEHAT FLEXIBLE BLOCK 02 |
      | field_node_ref | blog_flexible_01        | blog_flexible_02        |
    And I create large paragraph of type 1c:
      | KEY                             | behat_1c                  |
      | field_prgf_1c_title             | BEHAT 1C                  |
      | field_prgf_1c_description:value | Lorem ipsum dolor sit 1c. |
      | :format                         | full_html                 |
      | field_prgf_1c_column            | behat_simple_block_01     |
    And I create large paragraph of type 2c:
      | KEY                 | behat_2c                |
      | field_prgf_2c_left  | behat_simple_block_02   |
      | field_prgf_2c_right | behat_flexible_block_01 |
    And I create large paragraph of type 4c:
      | KEY                          | behat_4c                  |
      | field_prgf_title             | BEHAT 4C                  |
      | field_prgf_description:value | Lorem ipsum dolor sit 4c. |
      | :format                      | full_html                 |
      | field_prgf_4c_1st            | behat_simple_block_01     |
      | field_prgf_4c_2nd            | behat_simple_block_02     |
      | field_prgf_4c_3rd            | behat_flexible_block_01   |
      | field_prgf_4c_4th            | behat_flexible_block_02   |
    And I create landing_page content:
      | KEY            | title                | field_lp_layout | field_content      |
      | landing_1c     | Behat Landing 1C     | one_column      | behat_1c           |
      | landing_2c     | Behat Landing 2C     | one_column      | behat_2c           |
      | landing_4c     | Behat Landing 4C     | one_column      | behat_4c           |
    And I am an anonymous user

  Scenario: See 1C On Landing Page
    Given I view node "landing_1c"
    Then I should see "BEHAT 1C"
    And I should see "Lorem ipsum dolor sit 1c."
    And I should see "BEHAT SIMPLE BLOCK TITLE 01"
    And I should see "BEHAT SIMPLE BLOCK BODY 01"
    And I should see a "i.fa-info" element

  Scenario: See 2C On Landing Page
    Given I view node "landing_2c"
    Then I should see "BEHAT SIMPLE BLOCK TITLE 02"
    And I should see "BEHAT SIMPLE BLOCK BODY 02"
    And I should see a "i.fa-user-plus" element
    And I should see "Behat Blog Flexible 01"

  Scenario: See 4C On Landing Page
    Given I view node "landing_4c"
    Then I should see "BEHAT 4C"
    And I should see "Lorem ipsum dolor sit 4c."
    And I should see "BEHAT SIMPLE BLOCK TITLE 01"
    And I should see "BEHAT SIMPLE BLOCK BODY 01"
    And I should see a "i.fa-info" element
    And I should see "BEHAT SIMPLE BLOCK TITLE 02"
    And I should see "BEHAT SIMPLE BLOCK BODY 02"
    And I should see a "i.fa-user-plus" element
    And I should see "Behat Blog Flexible 01"
    And I should see "Behat Blog Flexible 02"

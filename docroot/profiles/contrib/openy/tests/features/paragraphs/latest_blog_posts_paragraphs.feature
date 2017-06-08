@openy @api
Feature: Latest Blogs Paragraphs
  Check that Latest Blog Posts paragraphs output other nodes based on some rules

  Background: Log in
    Given I am logged in as a user with the "Editor" role
    And I create taxonomy_term of type blog_category:
      | name               |
      | BEHAT CATEGORY ONE |
    And I create paragraph of type latest_blog_posts:
      | KEY          |
      | latest_blogs |
    And I create paragraph of type latest_blog_posts_branch:
      | KEY            |
      | branch_blogs   |
      | branch_blogs_2 |
    And I create paragraph of type latest_blog_posts_camp:
      | KEY        |
      | camp_blogs |
    And I create large branch content:
      | KEY                                 | behat_branch_01 | behat_branch_02 |
      | title                               | BEHAT BRANCH 01 | BEHAT BRANCH 02 |
      | field_location_address:country_code | US              | US              |
      | :address_line1                      | Main road 10    | Main road 10    |
      | :locality                           | Seattle         | Seattle         |
      | :administrative_area                | WA              | WA              |
      | :postal_code                        | 98101           | 98101           |
      | field_location_coordinates:lat      | 47.293433       | 47.293433       |
      | :lng                                | -122.238717     | -122.238717     |
      | field_location_phone                | +1234567890     | +1234567890     |
      | field_content                       | branch_blogs    | branch_blogs_2  |
    And I create large camp content:
      | KEY                                 | behat_camp_01      |
      | title                               | BEHAT CAMP 01      |
      | field_camp_menu_links:uri           | internal:/register |
      | :title                              | Registration       |
      | field_location_address:country_code | US                 |
      | :address_line1                      | Main road 10       |
      | :locality                           | Seattle            |
      | :administrative_area                | WA                 |
      | :postal_code                        | 98101              |
      | field_location_coordinates:lat      | 47.293433          |
      | :lng                                | -122.238717        |
      | field_location_phone                | +1234567890        |
      | field_content                       | camp_blogs         |
    And I create blog content:
      | title                                              | field_blog_location | promote | created           |
      | Behat Lifestyle changes beyond the first two weeks | behat_branch_01     | 1       | 2037-10-17 8:00am |
      | Behat Start training now for the Y Run             | behat_branch_01     | 1       | 2037-10-17 8:01am |
      | Behat Grown-up fun in Adult Sports Leagues         | behat_branch_01     | 1       | 2037-10-17 8:02am |
      | Behat Mood-boosting foods                          | behat_branch_01     | 1       | 2037-10-17 8:03am |
      | Behat Mango-Avocado Salsa                          | behat_branch_02     | 1       | 2037-10-17 8:04am |
      | Behat Add a water workout to your routine          | behat_branch_02     | 1       | 2037-10-17 8:05am |
      | Behat Don’t let summer sneak up on your family     | behat_camp_01       | 1       | 2037-10-17 8:06am |
      | Behat Add a water workout to your routine          | behat_camp_01       | 1       | 2037-10-17 8:07am |
      | Behat Nourish yourself with healthy fats           | behat_camp_01       | 1       | 2037-10-17 8:08am |
      | Behat Cheddar-Cannelini Fondue                     | behat_camp_01       | 1       | 2037-10-17 8:09am |
      | Behat Why you should give walking a try            | behat_camp_01       | 1       | 2037-10-17 8:10am |
      | Behat Community Outreach                           | behat_camp_01       | 1       | 2037-10-17 8:11am |
    Then I create landing_page content:
      | KEY                 | title               | field_lp_layout | field_content |
      | behat_landing_blogs | Behat Landing Blogs | one_column      | latest_blogs  |

  Scenario: Paste latest blog post
    Given I view node "behat_landing_blogs"
    Then I should see "Behat Community Outreach"
    And I should see "Behat Why you should give walking a try"
    And I should see "Behat Cheddar-Cannelini Fondue"
    And I should see "Behat Nourish yourself with healthy fats"
    And I should see "Behat Add a water workout to your routine"
    And I should see "Behat Don’t let summer sneak up on your family"
    When I click "Load More"
    Then I should see "Behat Add a water workout to your routine"
    And I should see "Behat Mango-Avocado Salsa"
    And I should see "Behat Mood-boosting foods"
    And I should see "Behat Grown-up fun in Adult Sports Leagues"
    And I should see "Behat Start training now for the Y Run"
    And I should see "Behat Lifestyle changes beyond the first two weeks"

  Scenario: Paste latest blog post (branch)
    Given I view node "behat_branch_01"
    Then I should see "Behat Mood-boosting foods"
    And I should see "Behat Grown-up fun in Adult Sports Leagues"
    And I should see "Behat Start training now for the Y Run"
    And I should not see "Behat Mango-Avocado Salsa"
    And I should not see "Behat Add a water workout to your routine"

  Scenario: Paste latest blog post for camp
    Given I view node "behat_camp_01"
    Then I should see "Behat Community Outreach"
    And I should see "Behat Why you should give walking a try"
    And I should see "Behat Cheddar-Cannelini Fondue"
    And I should see "Behat Nourish yourself with healthy fats"
    And I should see "Behat Add a water workout to your routine"
    And I should see "Behat Don’t let summer sneak up on your family"

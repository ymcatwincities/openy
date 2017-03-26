@openy @api
Feature: Latest Blogs Paragraphs
  Check that Latest Blog Posts paragraphs output other nodes based on some rules

  Background: Log in
    Given I am logged in as a user with the "Editor" role

  Scenario: Paste latest blog post
    And I go to "/node/add/landing_page"
    And I fill in "Title" with "Landing"
    And I select "One Column" from "Layout"
    When I press "Add Latest blog posts"
    And I press "Save and publish"
    Then I should see "Community Outreach"
    And I should see "Start training now for the Y Run"
    And I should see "Why you should give walking a try"
    And I should see "Don’t let summer sneak up on your family"
    And I should see "Add a water workout to your routine"
    And I should see "Grown-up fun in Adult Sports Leagues"
    When I click "Load More"
    Then I should see "Nourish yourself with healthy fats"
    And I should see "Cheddar-Cannelini Fondue"
    And I should see "Mood-boosting foods"

  Scenario: Paste latest blog post (branch)
    When I go to "/locations/west-ymca"
    And I click "Edit"
    And I should see "Type: Latest blog posts (branch)" in the "content_area"
    And I press "Save and keep published"
    Then I should see "Mood-boosting foods"
    And I should see "Grown-up fun in Adult Sports Leagues"
    And I should see "Start training now for the Y Run"
    And I should not see "Mango-Avocado Salsa"
    And I should not see "Add a water workout to your routine"

  Scenario: Paste latest blog post for camp
    Given I go to "/node/add/camp"
    And I press "Add Latest blog posts (camp)" in the "content_area"
    And I fill in "Title" with "Camp One"
    And I fill in the following:
      | URL | /register |
      | Link text | Registration |
    And I fill in the following:
      | Street address | Wood road 115  |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
      | Latitude       | 46.293433      |
      | Longitude      | -123.238717    |
      | Phone          | +1234567890    |
    And I press "Add Latest blog posts (camp)"
    And I press "Save and publish"

    And I go to "/node/add/blog"
    And I fill in "Title" with "OpenY Blog post"
    And I select "Camp One" from "Location"
    And I fill in the following:
      | Category | Category One |
      | Description | This could be a draft for a wonderful post. |
    When I press "Save and publish"
    When I go to "/camps/camp-one"
    And I should see "OpenY Blog post"

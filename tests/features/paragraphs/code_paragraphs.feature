@openy @api @code-paragraph
Feature: Code Paragraphs
  Check Code paragraphs can be created and displayed

  Background: Create basic landing page
    Given I am logged in as a user with the "Editor" role
    Then I create large block_content of type code_block:
      | KEY                 | behat_code_block_01                                               |
      | info                | BEHAT CODE BLOCK 01                                               |
      | field_code:value    | <script src="http://example.com" /><iframe name="test_iframe" />  |
      | :format             | code                                                              |
    And I create large paragraph of type code:
      | KEY                             | behat_code_prgf_1       |
      | field_prgf_code_block           | behat_code_block_01     |
    And I create landing_page content:
      | KEY            | title                | field_lp_layout | field_content      |
      | landing_code   | Behat Landing Code   | one_column      | behat_code_prgf_1  |
    And I am an anonymous user

  Scenario: See Code Paragraph On Landing Page
    Given I view node "landing_code"
    And I should see a "script[src='http://example.com']" element
    And I should see a "iframe[name='test_iframe']" element

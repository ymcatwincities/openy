@openy @api @code-paragraph
Feature: Code Paragraphs
  Check Code paragraphs can be created and displayed

  Background: Create basic landing page
    Given I create large block_content of type code_block:
      | KEY                 | behat_code_block_01                                              |
      | info                | BEHAT CODE BLOCK 01                                              |
      | field_code:value    | <script src="http://example.com" /><iframe name="test_iframe" /> |
      | :format             | code                                                             |
    And I create large paragraph of type code:
      | KEY                             | behat_code_prgf_01  |
      | field_prgf_code_block           | behat_code_block_01 |
    And I create landing_page content:
      | KEY            | title                | field_lp_layout | field_content      |
      | landing_code   | Behat Landing Code   | one_column      | behat_code_prgf_01 |

  Scenario: See Code Paragraph On Landing Page
    Given I view node "landing_code"
    Then I should see a "script[src='http://example.com']" element
    And I should see a "iframe[name='test_iframe']" element

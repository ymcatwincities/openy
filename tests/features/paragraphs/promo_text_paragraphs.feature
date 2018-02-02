@openy @api @promo-text-paragraph
Feature: Promo Text Paragraphs
  Check Promo Text paragraphs can be created and displayed

  Background: Create basic landing page
    Given I create large paragraph of type promo_text:
      | KEY                      | behat_promo_text_prgf_01                                                                                                                                                                      |
      | field_description:format | full_html                                                                                                                                                                                     |
      | field_description:value  | <h2>MEMBERSHIP</h2><p>Winter is here! $0 joiner fee now through 2/11/18. Get started today.&nbsp;<a href="membership">Learn more and get started</a>.</p>                                     |
      | field_sidebar:format     | full_html                                                                                                                                                                                     |
      | field_sidebar:value      | <h2>Schedules</h2><ul><li><a href="schedules/group-exercise-classes">Group Exercise &amp; Swim Schedule</a></li><li><a href="schedules/programs-classes">Programs &amp; Classes</a></li></ul> |
    And I create landing_page content:
      | KEY                | title                    | field_lp_layout | field_header_content     |
      | landing_promo_text | Behat Landing Promo Text | one_column      | behat_promo_text_prgf_01 |

  Scenario: See Promo Text Paragraph On Landing Page
    Given I view node "landing_promo_text"
    And I should see a "div.paragraph.paragraph--type--promo-text div.left a[href='membership']" element
    And I should see a "div.paragraph.paragraph--type--promo-text div.right a[href='schedules/programs-classes']" element

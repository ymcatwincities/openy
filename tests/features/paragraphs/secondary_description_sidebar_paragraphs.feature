@openy @api @secondary-description-sidebar-paragraph
Feature: Secondary Description and Sidebar Paragraphs
  Check Secondary Description and Sidebar paragraphs can be created and displayed

  Background: Create basic landing page
    Given I create large block_content of type basic_block:
      | KEY                       | behat_basic_block_01                                                                                                                                      | behat_basic_block_02                                                                                                                                                                          |
      | info                      | BEHAT BASIC BLOCK 01                                                                                                                                      | BEHAT BASIC BLOCK 02                                                                                                                                                                          |
      | field_block_content:value | <h2>MEMBERSHIP</h2><p>Winter is here! $0 joiner fee now through 2/11/18. Get started today.&nbsp;<a href="membership">Learn more and get started</a>.</p> | <h2>SCHEDULES</h2><ul><li><a href="schedules/group-exercise-classes">Group Exercise &amp; Swim Schedule</a></li><li><a href="schedules/programs-classes">Programs &amp; Classes</a></li></ul> |
      | :format                   | full_html                                                                                                                                                 | full_html                                                                                                                                                                                     |
    And I create large paragraph of type secondary_description_sidebar:
      | KEY                             | behat_secondary_description_sidebar_prgf_01 |
      | field_prgf_left_column_block    | behat_basic_block_01                        |
      | field_prgf_right_column_block   | behat_basic_block_02                        |
    And I create landing_page content:
      | KEY                                   | title                                                | field_lp_layout | field_header_content                        |
      | landing_secondary_description_sidebar | Behat Landing with Secondary Description and Sidebar | one_column      | behat_secondary_description_sidebar_prgf_01 |

  Scenario: See Secondary Description and Sidebar Paragraph On Landing Page
    Given I view node "landing_secondary_description_sidebar"
    And I should see a "div.paragraph.paragraph--type--secondary-description-sidebar div.left a[href='membership']" element
    And I should see a "div.paragraph.paragraph--type--secondary-description-sidebar div.right a[href='schedules/programs-classes']" element

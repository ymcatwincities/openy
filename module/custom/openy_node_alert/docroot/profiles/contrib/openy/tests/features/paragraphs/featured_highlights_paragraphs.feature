@openy @api @featured-highlights-paragraph
Feature: Featured Highlights Paragraphs
  Check Featured Highlights paragraphs can be created and displayed

  Background: Create basic landing page
    Given I create media of type image:
      | KEY      | name     | field_media_image |
      | image_01 | Image 01 | health.jpg        |
      | image_02 | Image 02 | swim_lessons.jpg  |
      | image_03 | Image 03 | child.jpg         |
    And I create large block_content of type featured_highlights_block:
      | KEY                              | behat_featured_highlights_block_01   | behat_featured_highlights_block_02   | behat_featured_highlights_block_03   |
      | info                             | BEHAT FEATURED HIGHLIGHTS BLOCK 01   | BEHAT FEATURED HIGHLIGHTS BLOCK 02   | BEHAT FEATURED HIGHLIGHTS BLOCK 03   |
      | field_block_content:value        | BEHAT FEATURED HIGHLIGHTS BLOCK 01   | BEHAT FEATURED HIGHLIGHTS BLOCK 02   | BEHAT FEATURED HIGHLIGHTS BLOCK 03   |
      | :format                          | full_html                            | full_html                            | full_html                            |
      | field_featured_hl_block_image    | image_01                             | image_02                             | image_03                             |
      | field_featured_hl_block_link:uri | http://openymca.org                  | http://openymca.org                  | http://openymca.org                  |
      | :title                           | Behat featured highlights block link 1 | Behat featured highlights block link 2 | Behat featured highlights block link 3 |
    And I create large paragraph of type featured_highlights:
      | KEY                             | behat_featured_highlights_prgf_01      |
      | field_prgf_title                | BEHAT FEATURED HIGHLIGHTS PARAGRAPH    |
      | field_prgf_grid_style           | 3                                      |
      | field_prgf_block_ref_unlim      | behat_featured_highlights_block_01, behat_featured_highlights_block_02, behat_featured_highlights_block_03     |
    And I create landing_page content:
      | KEY                           | title                               | field_lp_layout | field_content                     |
      | landing_featured_highlights   | Behat Landing Featured Highlights   | one_column      | behat_featured_highlights_prgf_01 |

  Scenario: See Featured Highlights Paragraph On Landing Page
    Given I view node "landing_featured_highlights"
    Then I should see a "div.paragraph.featured-highlights" element
    And I should see a "div.col-sm-4 article.featured-highlights-block a[title='Behat featured highlights block link 1']" element
    And I should see a "div.col-sm-4 article.featured-highlights-block a[title='Behat featured highlights block link 2']" element
    And I should see a "div.col-sm-4 article.featured-highlights-block a[title='Behat featured highlights block link 3']" element

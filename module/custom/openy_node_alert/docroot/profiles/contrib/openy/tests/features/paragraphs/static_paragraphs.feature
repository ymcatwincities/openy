@openy @api
Feature: Static Paragraphs
  Check that static paragraphs can be created and displayed

  Background: Create basic landing page
    Given I am logged in as a user with the "Editor" role
    Then I create media of type image:
      | KEY              | name             | field_media_image |
      | image_01         | Image 01         | image_01.png      |
      | gallery_image_01 | Gallery Image 01 | gallery_01.png    |
      | gallery_image_02 | Gallery Image 02 | gallery_02.png    |
      | story_image_01   | Story Image O1   | story_01.png      |
    And I create large block_content of type simple_block:
      | KEY                                | behat_menu_block_01      |
      | info                               | BEHAT MENU BLOCK 01      |
      | field_menu_block_links:uri         | http://openymca.org      |
      | :title                             | Behat menu block link 01 |
      | field_menu_block_text_color:value1 | Blue                     |
      | :value2                            | Blue                     |
      | field_menu_block_text_color        | 1                        |
    And I create large block_content of type flexible_content:
      | KEY                                | behat_menu_block_01      |
      | info                               | BEHAT MENU BLOCK 01      |
      | field_menu_block_links:uri         | http://openymca.org      |
      | :title                             | Behat menu block link 01 |
      | field_menu_block_text_color:value1 | Blue                     |
      | :value2                            | Blue                     |
      | field_menu_block_text_color        | 1                        |
    And I create taxonomy_term of type color:
      | KEY       | name            | field_color |
      | green     | Behat Green     | 00FF00      |
      | dark_blue | Behat Dark Blue | 008BD0      |
      | white     | Behat White     | FFFFFF      |
    And I create large block_content of type menu_block:
      | KEY                         | behat_menu_block_01                                                          |
      | info                        | BEHAT MENU BLOCK 01                                                          |
      | field_menu_block_links:uri  | http://openymca.org, http://openymca.org, internal:/give                     |
      | :title                      | Behat menu block link 01, Behat menu block link 02, Behat menu block link 03 |
      | field_menu_block_color      | dark_blue, dark_blue                                                         |
      | field_menu_block_text_color | white                                                                        |
    And I create large paragraph of type small_banner:
      | KEY                    | behat_small_banner |
      | field_prgf_headline    | BEHAT SMALL BANNER |
      | field_prgf_image       | image_01           |
      | field_prgf_color       | green              |
      | field_prgf_description | Enjoy the Open Y    |
    And I create large paragraph of type banner:
      | KEY                    | behat_banner        |
      | field_prgf_headline    | BEHAT BANNER        |
      | field_prgf_image       | image_01            |
      | field_prgf_color       | green               |
      | field_prgf_description | Enjoy the Open Y     |
      | field_prgf_link:uri    | http://openymca.org |
      | :title                 | Read about Open Y    |
    And I create large paragraph of type gallery:
      | KEY                    | behat_gallery                      |
      | field_prgf_headline    | BEHAT GALLERY                      |
      | field_prgf_images      | gallery_image_01, gallery_image_02 |
      | field_prgf_description | The description is here.           |
      | field_prgf_link:uri    | http://openymca.org                |
      | :title                 | Read about Open Y                   |
    And I create paragraph of type simple_content:
      | KEY          | field_prgf_description |
      | behat_simple | Simple text is here.   |
    And I create large paragraph of type grid_columns:
      | KEY                                   | behat_grid_column_01       |
      | field_prgf_clm_headline               | We Appreciate Your Support |
      | field_prgf_clm_class                  | flag                       |
      | field_prgf_grid_clm_description:value | We rely on donations.      |
      | :format                               | full_html                  |
      | field_prgf_clm_link:uri               | internal:/donate           |
      | :title                                | Donate                     |
    And I create paragraph of type grid_content:
      | KEY                | field_prgf_grid_style | field_grid_columns   |
      | behat_grid_content | 2                     | behat_grid_column_01 |
    And I create large paragraph of type promo_card:
      | KEY                    | behat_promo_card                      |
      | field_prgf_title       | BEHAT PROMO                           |
      | field_prgf_headline    | Open Y is free to try!                 |
      | field_prgf_description | Setup a website and see how it works. |
      | field_prgf_link:uri    | http://openymca.org                   |
      | :title                 | Go!                                   |
    And I create large paragraph of type story_card:
      | KEY                 | behat_story_card                          |
      | field_prgf_title    | BEHAT NEW STORY                           |
      | field_prgf_headline | I discovered Open Y. And that looks great! |
      | field_prgf_link:uri | http://openymca.org                       |
      | :title              | Discover Open Y                            |
    And I create large paragraph of type teaser:
      | KEY                    | behat_teaser           |
      | field_prgf_title       | BEHAT MY TEASER        |
      | field_prgf_description | Lorem ipsum dolor sit. |
      | field_prgf_image       | story_image_01         |
      | field_prgf_link:uri    | internal:/test         |
      | :title                 | Test link              |
    And I create large paragraph of type lto:
      | KEY                    | behat_lto                  |
      | field_lto_title        | BEHAT MY LTO               |
      | field_lto_subtitle     | Lorem ipsum dolor sit lto. |
      | field_lto_link:uri     | internal:/test             |
      | :title                 | Test link                  |
    And I create large paragraph of type microsites_menu:
      | KEY                             | behat_microsites_menu_01 |
      | field_prgf_block_ref            | behat_menu_block_01      |
    And I create landing_page content:
      | KEY                  | title                      | field_lp_layout | field_header_content     |
      | landing_small_banner | Behat Landing Small Banner | one_column      | behat_small_banner       |
      | landing_banner       | Behat Landing Banner       | one_column      | behat_banner             |
      | landing_gallery      | Behat Landing Gallery      | one_column      | behat_gallery            |
      | landing_simple       | Behat Landing Simple       | one_column      | behat_simple             |
      | landing_microsites   | Behat Landing Microsites   | one_column      | behat_microsites_menu_01 |
    And I create landing_page content:
      | KEY            | title                | field_lp_layout | field_content      |
      | landing_grid   | Behat Landing Grid   | one_column      | behat_grid_content |
      | landing_teaser | Behat Landing Teaser | one_column      | behat_teaser       |
      | landing_lto    | Behat Landing LTO    | one_column      | behat_lto          |
    And I create landing_page content:
      | KEY           | title               | field_lp_layout | field_sidebar_content |
      | landing_promo | Behat Landing Promo | two_column      | behat_promo_card      |
      | landing_story | Behat Landing Story | two_column      | behat_story_card      |

  Scenario: See Small Banner On Landing Page
    Given I view node "landing_small_banner"
    Then I should see "BEHAT SMALL BANNER"
    And I should see a ".paragraph--type--small-banner .banner-image img" element
    And I should see the text "Enjoy the Open Y"

  Scenario: See Banner On Landing Page
    Given I view node "landing_banner"
    Then I should see the heading "BEHAT BANNER"
    And I should see a ".paragraph--type--banner .banner-image img" element
    And I should see the text "Enjoy the Open Y"
    And I should see the link "Read about Open Y"

  Scenario: See Gallery On Landing Page
    Given I view node "landing_gallery"
    Then I should see the heading "BEHAT GALLERY"
    And I should see "The description is here."
    And I should see a ".carousel img" element
    And I should see the link "Read about Open Y"

  Scenario: See Simple Content On Landing Page
    Given I view node "landing_simple"
    Then I should see "Simple text is here."

  Scenario: See Grid Content On Landing Page
    Given I view node "landing_grid"
    Then I should see the heading "We Appreciate Your Support"
    And I should see a "i.fa-flag" element
    And I should see "We rely on donations."
    And I should see the link "Donate"

  Scenario: See Promo Card On Landing Page
    Given I view node "landing_promo"
    Then I should see the heading "BEHAT PROMO"
    And I should see the heading "Open Y is free to try!"
    And I should see "Setup a website and see how it works."
    And I should see the link "Go!"

  Scenario: See Story Card On Landing Page
    Given I view node "landing_story"
    Then I should see the heading "BEHAT NEW STORY"
    And I should see "I discovered Open Y. And that looks great!"
    And I should see the link "Discover Open Y"

  Scenario: See Teaser On Landing Page
    Given I view node "landing_teaser"
    Then I should see the heading "BEHAT MY TEASER"
    And I should see "Lorem ipsum dolor sit."
    And I should see a ".subprogram-listing-item img" element
    And I should see the link "Test link"

  Scenario: See Microsites On Landing Page
    Given I view node "landing_microsites"
    Then I should see the link "Behat menu block link 01"
    And I should see the link "Behat menu block link 02"
    And I should see the link "Behat menu block link 03"
    And I should see a ".microsites-menu__wrapper[style='color: #FFFFFF; background: linear-gradient(to top, #008BD0, #008BD0);']" element
    And I should see a ".microsites-menu--hide-main-menu" element

  Scenario: See LTO On Landing Page
    Given I view node "landing_lto"
    Then I should see "BEHAT MY LTO"
    And I should see "Lorem ipsum dolor sit lto."
    And I should see the link "Test link"

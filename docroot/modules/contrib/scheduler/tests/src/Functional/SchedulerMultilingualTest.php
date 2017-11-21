<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the scheduling functions for node translations.
 *
 * @group scheduler
 */
class SchedulerMultilingualTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   */
  public static $modules = ['content_translation'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a user with the required translation permissions.
    // 'administer languages' for url admin/config/regional/content-language.
    // 'administer content translation' to show the list of content fields at
    // url admin/config/regional/content-language.
    // 'create content translations' for the 'translations' tab on node pages
    // url node/*/translations.
    // 'translate any entity' for the 'add translation' link on the translations
    // page, url node/*/translations/add/.
    $this->translatorUser = $this->drupalCreateUser([
      'administer languages',
      'administer content translation',
      'create content translations',
      'translate any entity',
    ]);

    // Get the additional role already assigned to the scheduler admin user
    // created in SchedulerBrowserTestBase and add this role to the translator
    // user, to avoid switching between users throughout this test.
    $admin_roles = $this->adminUser->getRoles();
    // Key 0 is 'authenticated' role. Key 1 is the first real role.
    $this->translatorUser->addRole($admin_roles[1]);
    $this->translatorUser->save();
    $this->drupalLogin($this->translatorUser);

    // Allow scheduler dates in the past to be published on next cron run.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'schedule')->save();

    // Enable the content type for translation.
    $this->ctm = $this->container->get('content_translation.manager');
    $this->ctm->setEnabled('node', $this->type, TRUE);

    // Make three additional languages available.
    // Do not add 'en' here.
    $this->langcodes = ['am', 'bg', 'ca'];
    ConfigurableLanguage::createFromLangcode($this->langcodes[0])->save();
    ConfigurableLanguage::createFromLangcode($this->langcodes[1])->save();
    ConfigurableLanguage::createFromLangcode($this->langcodes[2])->save();

    // Get the language names and store for later use.
    $languages = \Drupal::languageManager()->getLanguages();
    $this->languages = [
      0 => ['code' => $this->langcodes[0], 'name' => $languages[$this->langcodes[0]]->getName()],
      1 => ['code' => $this->langcodes[1], 'name' => $languages[$this->langcodes[1]]->getName()],
      2 => ['code' => $this->langcodes[2], 'name' => $languages[$this->langcodes[2]]->getName()],
      3 => ['code' => 'en', 'name' => $languages['en']->getName()],
    ];
  }

  /**
   * Helper function to assert the published status of translations.
   *
   * @param int $nid
   *   The node id of the node to check.
   * @param string $description
   *   Text explaining what part of the test is being checked.
   * @param array $st
   *   Array of expected status values for the translations. The original
   *   content status is first, followed by any number of translations.
   */
  private function checkStatus($nid, $description, array $st) {

    // Reset the cache and reload the node.
    $this->nodeStorage->resetCache([$nid]);
    $node = $this->nodeStorage->load($nid);

    foreach ($st as $key => $status) {
      if ($key == 0) {
        // Key 0 is the original, so we just check $node.
        $this->assertEqual($node->isPublished(), $status,
          sprintf('%s: The original content (%s) is %s', $description, $this->languages[$key]['name'], ($status ? 'published' : 'unpublished')));
      }
      else {
        // Key > 0 are the translations, which we get using the Content
        // Translation Manager getTranslationMetadata() function.
        $trans = $this->ctm->getTranslationMetadata($node->getTranslation($this->languages[$key]['code']));
        $trans = $node->getTranslation($this->languages[$key]['code']);
        $this->assertEqual($trans->isPublished(), $status,
          sprintf('%s: Translation %d (%s) is %s', $description, $key, $this->languages[$key]['name'], ($status ? 'published' : 'unpublished')));
      }
    }
  }

  /**
   * Test creating translations with independent scheduling.
   *
   * @dataProvider dataPublishingTranslations()
   */
  public function testPublishingTranslations($publish_on_translatable, $unpublish_on_translatable, array $expected_status_values_before, array $expected_status_values_after) {

    // Set the scheduler fields to be translatable yes/no depending on the
    // parameters passed in.
    $this->drupalGet('admin/config/regional/content-language');
    $settings = [
      'edit-settings-node-page-settings-language-language-alterable' => TRUE,
      'edit-settings-node-page-fields-publish-on' => $publish_on_translatable,
      'edit-settings-node-page-fields-unpublish-on' => $unpublish_on_translatable,
    ];
    // The submit shows the updated values, so no need for second get.
    $this->submitForm($settings, 'Save configuration');

    // Create a node. This will known as the 'original' before any translations.
    // It is unpublished with no scheduled date.
    $create = [
      'type' => $this->type,
      'title' => $this->languages[0]['name'] . '(0) - Unpublished and not scheduled',
      'langcode' => $this->languages[0]['code'],
      'status' => FALSE,
    ];
    $node = $this->drupalCreateNode($create);

    // Create the first translation, published now with no scheduled date.
    $this->drupalGet('node/' . $node->id() . '/translations/add/' . $this->languages[0]['code'] . '/' . $this->languages[1]['code']);
    $edit = [
      'title[0][value]' => $this->languages[1]['name'] . '(1) - Published now',
      'publish_on[0][value][date]' => '',
      'publish_on[0][value][time]' => '',
    ];
    // At core 8.4 an enhancement will be committed to change the 'save and ...'
    // button into a 'save' with a corresponding status checkbox. This test has
    // to pass at 8.3 but the core change will not be backported. Hence derive
    // the button text and whether we need a 'status'field.
    // @see https://www.drupal.org/node/2873108
    $checkbox = $this->xpath('//input[@type="checkbox" and @id="edit-status-value"]');
    if ($checkbox) {
      $edit['status[value]'] = TRUE;
      $save_button_text = 'Save';
    }
    else {
      $save_button_text = 'Save and publish';
    }
    $this->submitForm($edit, $save_button_text);

    // Create the second translation, to be published in the future.
    $this->drupalGet('node/' . $node->id() . '/translations/add/' . $this->languages[0]['code'] . '/' . $this->languages[2]['code']);
    $edit = [
      'title[0][value]' => $this->languages[2]['name'] . '(2) - Publish in the future',
      'publish_on[0][value][date]' => date('Y-m-d', strtotime('+2 day', REQUEST_TIME)),
      'publish_on[0][value][time]' => date('H:i:s', strtotime('+2 day', REQUEST_TIME)),
    ];
    $this->submitForm($edit, $save_button_text);

    // Create the third translation, to be published in the past.
    $this->drupalGet('node/' . $node->id() . '/translations/add/' . $this->languages[0]['code'] . '/' . $this->languages[3]['code']);
    $edit = [
      'title[0][value]' => $this->languages[3]['name'] . '(3) - Publish in the past',
      'publish_on[0][value][date]' => date('Y-m-d', strtotime('-2 day', REQUEST_TIME)),
      'publish_on[0][value][time]' => date('H:i:s', strtotime('-2 day', REQUEST_TIME)),
    ];
    $this->submitForm($edit, $save_button_text);

    // For info only.
    $this->drupalGet($this->languages[0]['code'] . '/node/' . $node->id() . '/translations');
    $this->drupalGet('admin/content/scheduled');

    // Check the status of all four pieces of content before running cron.
    $this->checkStatus($node->id(), 'Before cron', $expected_status_values_before);
    $this->cronRun();

    // For info only.
    $this->drupalGet('admin/content/scheduled');
    $this->drupalGet('admin/content');
    $this->drupalGet('admin/reports/dblog');
    $this->drupalGet($this->languages[0]['code'] . '/node/' . $node->id() . '/translations');

    // Check all the status values after running cron.
    $this->checkStatus($node->id(), 'After cron', $expected_status_values_after);

  }

  /**
   * Provides data for testPublishingTranslations().
   *
   * Case 1 when the date is translatable and can differ between translations.
   * Case 2 when the date is not translatable and the behavior should be
   *   consistent over all translations.
   *
   * @return array
   *   The test data.
   */
  public function dataPublishingTranslations() {
    return [
      'publish_on translatable' => [
        TRUE,
        FALSE,
        [FALSE, TRUE, FALSE, FALSE],
        [FALSE, TRUE, FALSE, TRUE],
      ],
    ];
    /*
    @TODO Fix module code before committing the 'publish_on not translatable'
    test
    // should be
    'publish_on not translatable' => [FALSE, FALSE,
    array(FALSE, FALSE, FALSE, FALSE), array(TRUE, TRUE, TRUE, TRUE)],
    // actual before and after
    'publish_on not translatable' => [FALSE, FALSE,
    array(FALSE, TRUE, FALSE, FALSE), array(TRUE, TRUE, FALSE, FALSE)],
    // actual before, but expected values after
    'publish_on not translatable' => [FALSE, FALSE,
    array(FALSE, TRUE, FALSE, FALSE), array(TRUE, TRUE, TRUE, TRUE)],
     */
  }

}

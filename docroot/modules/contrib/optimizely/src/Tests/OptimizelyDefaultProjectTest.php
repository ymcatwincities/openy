<?php

namespace Drupal\optimizely\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test various aspects of the default project.
 *
 * 1. The default project is available but disabled in the project listing page
 *    after the module has been enabled.
 *
 * 2. A message in the project listing page directs the administrator to go
 *    to the module settings page to enter the Optimizely account value.
 *
 * 3. Accessing the account setting page should be blank by default with
 *    a message informing the user that the account setting will be used
 *    for the default project number.
 *
 * 4. Test adding the account setting redirects to the project listing page
 *    with the account number listed as the disabled project dumber for the
 *    default project entry.
 *
 * 5. The default project cannot be enabled until the account number is entered
 *    on the settings page.
 *
 * 6. Enabling the default project with the default path setting of sidewide "*"
 *    should result in the snippet being displayed on the site's front page.
 *
 * @group Optimizely
 */
class OptimizelyDefaultProjectTest extends WebTestBase {

  protected $settingsPage = 'admin/config/system/optimizely/settings';
  protected $listingPage = 'admin/config/system/optimizely';
  protected $updateDefaultProjPage = 'admin/config/system/optimizely/add_update/1';

  protected $ajaxCallbackUrl = 'ajax/optimizely';

  protected $optimizelyPermission = 'administer optimizely';

  protected $privilegedUser;

  protected $optimizelyAccountId;

  /**
   * List of modules to enable.
   *
   * @var array
   */
  public static $modules = ['optimizely'];

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {

    return [
      'name' => 'Optimizely Default Project',
      'description' => 'Test the existence of a disabled default project.
         When it is enabled after adding the Optimizely account ID,
         the default snippet is added to the front page (default) of the site.',
      'group' => 'Optimizely',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    $this->privilegedUser = $this->drupalCreateUser([$this->optimizelyPermission]);
  }

  /**
   * Test various aspects of the Default project.
   *
   * 1. The default project is available but disabled in the project listing
   *    page after the module has been enabled.
   *
   * 2. A message in the project listing page directs the administrator to go
   *    to the module settings page to enter the Optimizely account value.
   *
   * 5. The default project cannot be enabled until the account number is
   *    entered on the settings page.
   */
  public function testDefaultProjectEnable() {

    // Access with privileged user.
    $this->drupalLogin($this->privilegedUser);

    // Look for entry in project listing page.
    $this->drupalGet($this->listingPage);

    $this->assertRaw('<td class="project-title-column disabled">Default</td>',
                      '<strong>Default project entry found on project listing page.</strong>',
                      'Optimizely');

    // Confirm default project is not enabled.
    $this->assertRaw('<input id="project-enable-1" name="project-1" type="checkbox" ' .
                      'value="1" class="form-checkbox" />',
                      '<strong>Default project checkbox exists.</strong>',
                      'Optimizely');
    $this->assertNoFieldChecked('project-enable-1',
                      '<strong>Default project is not enabled.</strong>',
                      'Optimizely');

    // Link to complete default project setup available.
    $this->assertLink('Account Info', 0,
                      '<strong>Link from default project to module settings page available.</strong>',
                      'Optimizely');

    // Navigate to Edit form for Default project.
    $this->drupalGet($this->updateDefaultProjPage);

    // Title field set to Default, not accessible.
    $this->assertPattern(
      ':<input( [^>]*? | )disabled="disabled"( [^>]*? | )id="edit-optimizely-project-title"( [^>]*? | )value="Default"( [^>]*?)?/>:',
      '<strong>Project title field is not editable and set to "Default"</strong>.',
      'Optimizely');

    // Project Code field not set (Undefined), not accessible.
    $this->assertPattern(
      ':<input( [^>]*? | )disabled="disabled"( [^>]*? | )id="edit-optimizely-project-code"( [^>]*? | )value="Undefined"( [^>]*?)?/>:',
      '<strong>Project code field is not editable and set to "Undefined".</strong>',
      'Optimizely');

    // Link to settings page to set account / Default project code.
    $this->assertLink('Account Info', 0,
                      '<strong>Link to settings page found to set Default project code.</strong>',
                      'Optimizely');

    // Check default Default project path is set to sitewide wild card.
    $this->assertPattern(':<textarea( [^>]*? | )id="edit-optimizely-path"( [^>]*?)?>\*</textarea>:',
                          '<strong>Default project path set to sitewide wild card "*".</strong>',
                          'Optimizely');

    // * 5. The default project can not be enabled
    // until the account number is entered on the settings page.
  }

  /**
   * Test settings for Default project.
   *
   * 3. Accessing the account setting page should be blank by default with
   *    a message informing the user that the account setting will be used
   *    for the default project number.
   *
   * 4. Test that adding the account setting redirects to the project listing
   *    page with the account number listed as the disabled project dumber for
   *    the default project entry.
   */
  public function testDefaultProjectSettings() {

    // Access with privileged user.
    $this->drupalLogin($this->privilegedUser);

    // Access generate module settings page.
    $this->drupalGet($this->settingsPage);

    // Check for blank setting (default)
    $this->assertFieldByName('optimizely_id', NULL,
      '<strong>The Optimizely ID field is blank on Account Info page</strong>',
      'Optimizely');

    // Add Optimizely account setting.
    $this->optimizelyAccountId = rand(1000000, 9999999);
    // N.B. Must use name attribute, not Id.
    $edit = [
      'optimizely_id' => $this->optimizelyAccountId,
    ];
    $this->drupalPostForm($this->settingsPage, $edit, t('Submit'));

    // Check that redirect to project page worked after entering
    // Optimizely account ID in setting page
    // $this->assertUrl('/admin/config/system/optimizely', $options = array(),
    // 'Redirected to project listing page -> /admin/config/system/optimizely
    // after submitting Optimizely account ID on setting page.');.
    $this->drupalGet($this->listingPage);

    // Check that the newly entered Optimizely ID is now listed
    // as the project ID for the Default project.
    $this->assertRaw('<td class="project-code-column disabled">' . $this->optimizelyAccountId . '</td>',
      '<strong>Default project is using the Optimizely account ID for project ID -> ' .
      $this->optimizelyAccountId . '.</strong>',
      'Optimizely');

    // Access add / edit project page for default project.
    $this->drupalGet($this->updateDefaultProjPage);

    // Check the project ID setting matches the Optimizely Account ID setting.
    $this->assertFieldByName('optimizely_project_code', $this->optimizelyAccountId,
      '<strong>The Optimizely Project Code matches the Optimizely account ID setting.</strong>',
      'Optimizely');

    // Enable the Default project.
    $edit = [
      'optimizely_enabled' => 1,
    ];
    $this->drupalPostForm($this->updateDefaultProjPage, $edit, t('Update'));

    // Go to project listings page.
    $this->drupalGet($this->listingPage);

    // Confirm default project *is* enabled.
    $this->assertRaw(
      '<input id="project-enable-1" name="project-1" checked="checked" ' .
      'type="checkbox" value="1" class="form-checkbox" />',
      '<strong>Default project *is* enabled on project listing page.</strong>',
      'Optimizely');

  }

  /**
   * Test use of Ajax to enable Default project.
   */
  public function testDefaultProjectListingAjax() {

    // Access with privileged user.
    $this->drupalLogin($this->privilegedUser);

    // Add Optimizely account setting so that Default Project can be enabled.
    $this->optimizelyAccountId = rand(1000000, 9999999);
    // N.B. Must use name attribute, not Id.
    $edit = [
      'optimizely_id' => $this->optimizelyAccountId,
    ];
    $this->drupalPostForm($this->settingsPage, $edit, t('Submit'));

    // Go to project listings page.
    $this->drupalGet($this->listingPage);

    // Confirm default project is disabled.
    $this->assertNoFieldChecked('project-enable-1',
      '<strong>Default project is disabled on project listing page.</strong>',
      'Optimizely');

    // Test that Ajax call succeeds. 1 == Default Project.
    $params = [
      'target_oid' => 1,
      'target_enable' => 1,
    ];

    $json_response = $this->drupalPost($this->ajaxCallbackUrl, 'application/json', $params);
    $resp_obj = json_decode($json_response);

    $this->assertEqual($resp_obj->status, 'updated',
      '<strong>Ajax returned status is "updated"</strong>', 'Optimizely');
    $this->assertEqual($resp_obj->message, '',
      '<strong>Ajax returned message is blank</strong>', 'Optimizely');

  }

}

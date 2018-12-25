<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\DashboardTestBase;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController::buildPurgers().
 *
 * @group purge_ui
 */
class DashboardPurgersTest extends DashboardTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_purger_test'];

  /**
   * Test the purgers section of the configuration form.
   *
   * @warning
   *   This test depends on raw HTML, which is a bit of a maintenance cost. At
   *   the same time, core's markup guarantees should keep us safe. Having that
   *   said, for the purpose of testing, raw HTML checking is very accurate :-).
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildPurgers
   */
  public function testPurgersSection() {
    $this->drupalLogin($this->admin_user);
    // Assert that without any enabled purgers, the form stays empty.
    $this->initializePurgersService();
    $this->drupalGet($this->route);
    $this->assertRaw('Each layer of caching on top of your site is cleared by a purger. Purgers are provided by third-party modules and support one or more types of cache invalidation.');
    $this->assertRaw('Drupal Origin');
    $this->assertRaw('Public Endpoint');
    $this->assertNoRaw('Purger A</th>');
    $this->assertNoRaw('Purger B</th>');
    $this->assertNoRaw('Purger C</th>');
    $this->assertNoRaw('Configurable purger</th>');
    // Assert that enabled purgers show up and have the right buttons attached.
    $this->initializePurgersService(['a', 'withform']);
    $this->drupalGet($this->route);
    $this->assertRaw('Purger A</a>');
    $this->assertRaw('Configurable purger</a>');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id0"');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/purger/id0/config/dialog"');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id0/delete"');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id1"');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id1/config/dialog"');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id1/delete"');
    // Assert that the purger-type supportability matrix shows the checkmarks.
    $this->assertRaw('core/misc/icons/73b355/check.svg');
    $this->assertRaw('width="18" height="18" alt="Supported" title="Supported" />');
    $this->assertNoRaw('<img supports="drupal-domain"');
    $this->assertNoRaw('<img supports="drupal-path"');
    $this->assertRaw('<img supports="drupal-tag"');
    $this->assertNoRaw('<img supports="drupal-regex"');
    $this->assertNoRaw('<img supports="drupal-wildcardpath"');
    $this->assertNoRaw('<img supports="drupal-wildcardurl"');
    $this->assertNoRaw('<img supports="drupal-url"');
    $this->assertNoRaw('<img supports="drupal-everything"');
    $this->assertNoRaw('<img supports="id0-domain"');
    $this->assertNoRaw('<img supports="id0-path"');
    $this->assertNoRaw('<img supports="id0-tag"');
    $this->assertNoRaw('<img supports="id0-regex"');
    $this->assertNoRaw('<img supports="id0-wildcardpath"');
    $this->assertNoRaw('<img supports="id0-wildcardurl"');
    $this->assertNoRaw('<img supports="id0-url"');
    $this->assertRaw('<img supports="id0-everything"');
    $this->assertNoRaw('<img supports="id1-domain"');
    $this->assertRaw('<img supports="id1-path"');
    $this->assertNoRaw('<img supports="id1-tag"');
    $this->assertNoRaw('<img supports="id1-regex"');
    $this->assertNoRaw('<img supports="id1-wildcardpath"');
    $this->assertNoRaw('<img supports="id1-wildcardurl"');
    $this->assertNoRaw('<img supports="id1-url"');
    $this->assertNoRaw('<img supports="id1-everything"');
    // Assert that the 'Add purger' button only shows up when it actually should.
    $this->assertRaw(t('Add purger'));
    $this->initializePurgersService(['a', 'b', 'c', 'withform', 'good']);
    $this->drupalGet($this->route);
    $this->assertNoRaw(t('Add purger'));
  }

}

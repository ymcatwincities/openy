<?php

namespace Drupal\panelizer\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests the updating of Layout IDs.
 *
 * @group Panelizer
 */
class PanelizerLayoutIDUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.panelizer.minimal.php.gz',
    ];
  }

  /**
   * Test updates.
   */
  public function testUpdate() {
    $this->runUpdates();

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->clickLink('Edit', 1);
    $this->assertResponse(200);

    $this->drupalGet('node/1');
    $this->assertResponse(200);

    $this->drupalGet('node/2');
    $this->assertResponse(200);
  }

}

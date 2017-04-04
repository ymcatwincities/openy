<?php

namespace Drupal\panelizer\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

/**
 * @group panelizer
 */
class PanelizerAddDefaultLinkTest extends WebTestBase {

  use PanelizerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'ctools',
    'layout_plugin',
    'node',
    'panelizer',
    'panels',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $account = User::load(1);
    $account->setPassword('foo')->save();
    $account->pass_raw = 'foo';
    $this->drupalLogin($account);
  }

  public function test() {
    $this->panelize('page');
    $this->assertLink('Add a new Panelizer default display');
    $this->unpanelize('page');
    $this->assertNoLink('Add a new Panelizer default display');
  }

}

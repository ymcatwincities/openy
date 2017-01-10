<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\purge_ui\Tests\PluginConfigFormTestBase;

/**
 * Testbase for \Drupal\purge_ui\Form\PurgerConfigFormBase derivatives.
 */
abstract class PurgerConfigFormTestBase extends PluginConfigFormTestBase {

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.purger_config_form';

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $routeDialog = 'purge_ui.purger_config_dialog_form';

  /**
   * {@inheritdoc}
   */
  protected function assertFormTitle() {
    $label = $this->purgePurgers->getLabels()['id0'];
    $this->assertTitle("Configure $label | Drupal");
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin() {
    $this->initializePurgersService([$this->plugin]);
  }

  /**
   * Return the ID argument given to the form.
   */
  protected function getId() {
    // Since initializePurgersService() autogenerates the IDs, ours is known.
    return 'id0';
  }

}

<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\purge_ui\Tests\PluginConfigFormTestBase;

/**
 * Testbase for \Drupal\purge_ui\Form\ProcessorConfigFormBase derivatives.
 */
abstract class ProcessorConfigFormTestBase extends PluginConfigFormTestBase {

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.processor_config_form';

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $routeDialog = 'purge_ui.processor_config_dialog_form';

  /**
   * {@inheritdoc}
   */
  protected function assertFormTitle() {
    $label = $this->purgeProcessors->getPlugins()[$this->plugin]['label'];
    $this->assertTitle("Configure $label | Drupal");
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin() {
    $this->initializeProcessorsService([$this->plugin]);
  }

}

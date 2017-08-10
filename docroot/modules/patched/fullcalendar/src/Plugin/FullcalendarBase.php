<?php

namespace Drupal\fullcalendar\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * @todo.
 */
abstract class FullcalendarBase extends PluginBase implements FullcalendarInterface {

  /**
   * @todo.
   *
   * @var \Drupal\views\Plugin\views\style\StylePluginBase
   */
  protected $style;

  /**
   * {@inheritdoc}
   */
  public function setStyle(StylePluginBase $style) {
    $this->style = $style;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function process(&$settings) {
  }

  /**
   * {@inheritdoc}
   */
  public function preView(&$settings) {
  }

}

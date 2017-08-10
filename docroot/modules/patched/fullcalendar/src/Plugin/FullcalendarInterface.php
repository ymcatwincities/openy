<?php

namespace Drupal\fullcalendar\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * @todo.
 */
interface FullcalendarInterface extends PluginInspectionInterface {

  public function setStyle(StylePluginBase $style);

  public function defineOptions();

  public function buildOptionsForm(&$form, FormStateInterface $form_state);

  public function submitOptionsForm(&$form, FormStateInterface $form_state);

  public function process(&$settings);

  public function preView(&$settings);

}

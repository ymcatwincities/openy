<?php
/**
 * @file
 * Contains \Drupal\webform\Form\WebformComponentEditForm.
 */


namespace Drupal\webform\Form;


use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for editing Webform components.
 */
class WebformComponentEditForm extends WebformComponentFormBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareComponent($node, $component) {
    $config = isset($node->webform['components'][$component]) ? $node->webform['components'][$component] : FALSE;
    $component = $this->componentManager->createInstance($config['type']);
    $component->setConfiguration($config);
    return $component;

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_component_edit';
  }

}

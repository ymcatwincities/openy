<?php

/**
 * @file
 * Contains \Drupal\webform\Form\WebformComponentAddForm.
 */

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\ComponentManager;
use Drupal\Core\Database\Connection;

/**
 * Provides a form for adding Webform components.
 */
class WebformComponentAddForm extends WebformComponentFormBase {
  /**
   * {inheritdoc}
   */
  protected function prepareComponent($node, $component) {
    /** @var \Drupal\webform\ComponentInterface $loaded_component */
    $loaded_component = $this->componentManager->createInstance($component);
    $loaded_component->setConfiguration(
      [
        'type' => \Drupal::request()->query->get('type'),
        'pid' => \Drupal::request()->query->get('pid'),
        'weight' => \Drupal::request()->query->get('weight'),
        'required' => \Drupal::request()->query->get('required'),
        'name' => \Drupal::request()->query->get('name'),
      ]
    );
    return $loaded_component;
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_component_add';
  }

  /**
   * {@inheritdoc}
   *
   * @todo Is this just leftover function?
   */
  public function validateComponentEditForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    //parent::submitForm($form, $form_state);
  }


}

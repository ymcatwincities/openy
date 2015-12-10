<?php

/**
 * @file
 * Contains \Drupal\webform\Plugin\WebformComponent\TextField.
 */

namespace Drupal\webform\Plugin\WebformComponent;

use Drupal\node\Entity\Node;
use Drupal\webform\ComponentBase;

/**
 * Provides a 'textfield' component.
 *
 * @Component(
 *   id = "textfield",
 *   label = @Translation("TextField"),
 *   description = @Translation("A textfield field.")
 * )
 */
class TextField extends ComponentBase {

  protected $supports_unique = TRUE;

  protected $supports_disabled = TRUE;

  protected $supports_placeholder = TRUE;

  protected $supports_width = TRUE;

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, Node $node = NULL) {
    // @TODO Load component default values.
    $component = array(
      'value' => '',
      'extra' => array(
        'width' => '',
        'placeholder' => '',
        'field_prefix' => '',
        'field_suffix' => '',
        'disabled' => '',
        'unique' => '',
        'maxlength' => '',
      ),
    );

    $config = $this->getConfiguration();

    // Begin with the parent object's basic form.
    $form = parent::buildForm($form, $form_state, $node);

    $form['value'] = array(
      '#type' => 'textfield',
      '#title' => t('Default value'),
      '#default_value' => $config['value'],
      '#description' => t('The default value of the field.'),
      '#size' => 60,
      '#maxlength' => 1024,
      '#weight' => 0,
    );





    $form['validation']['maxlength'] = array(
      '#type' => 'textfield',
      '#title' => t('Maxlength'),
      '#default_value' => $config['extra']['maxlength'],
      '#description' => t('Maximum length of the textfield value.'),
      '#size' => 5,
      '#maxlength' => 10,
      '#weight' => 2,
      '#parents' => array('extra', 'maxlength'),
    );

    return $form;
  }
}

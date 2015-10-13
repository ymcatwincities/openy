<?php

/**
 * @file
 * Contains \Drupal\embed_test\Plugin\EmbedType\Animal.
 */

namespace Drupal\embed_test\Plugin\EmbedType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\embed\EmbedType\EmbedTypeBase;

/**
 * Animal test embed type.
 *
 * @EmbedType(
 *   id = "embed_test_animal",
 *   label = @Translation("Animals")
 * )
 */
class Animal extends EmbedTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();

    $form['animal_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Animal type'),
      '#options' => array(
        'vertebrates' => $this->t('Vertebrates (with backbone)'),
        'invertebrates' => $this->t('Invertebrates (without backbone)'),
      ),
      '#default_value' => $this->getConfigurationValue('animal_type'),
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => array($form_object, 'updateTypeSettings'),
        'effect' => 'fade',
      ),
    );

    if ($this->getConfigurationValue('animal_type') === 'vertebrates') {
      $form['allowed_vertebrates'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Limit allowed vertebrates'),
        '#options' => array(
          'amphibians' => $this->t('Amphibians'),
          'birds' => $this->t('Birds'),
          'fish' => $this->t('Fish'),
          'mammals' => $this->t('Mammals'),
          'reptiles' => $this->t('Reptiles'),
        ),
        '#default_value' => $this->getConfigurationValue('allowed_vertebrates', array()),
      );
    }

    return $form;
  }

}

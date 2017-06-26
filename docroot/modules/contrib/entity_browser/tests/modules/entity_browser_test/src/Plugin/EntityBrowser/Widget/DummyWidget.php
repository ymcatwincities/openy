<?php

/**
 * Contains \Drupal\entity_browser_test\Plugin\EntityBrowser\Widget\DummyWidget.
 */

namespace Drupal\entity_browser_test\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;

/**
 * Dummy widget implementation for test purposes.
 *
 * @EntityBrowserWidget(
 *   id = "dummy",
 *   label = @Translation("Dummy widget"),
 *   description = @Translation("Dummy widget existing for testing purposes.")
 * )
 */
class DummyWidget extends WidgetBase {

  /**
   * Entity to be returned.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  public $entity;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['text' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {
    return ['#markup' => $this->configuration['text']];
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $this->selectEntities([$this->entity], $form_state);
  }

}

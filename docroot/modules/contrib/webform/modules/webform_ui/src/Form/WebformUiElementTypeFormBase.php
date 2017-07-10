<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Form\WebformDialogFormTrait;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a abstract element type webform for a webform element.
 */
abstract class WebformUiElementTypeFormBase extends FormBase {

  use WebformDialogFormTrait;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformUiElementTypeFormBase object.
   *
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(WebformElementManagerInterface $element_manager) {
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);
  }

  /**
   * Gets the sorted definition of all WebformElement plugins.
   *
   * @return array
   *   An array of WebformElement plugin definitions. Keys are element types.
   */
  protected function getDefinitions() {
    $definitions = $this->elementManager->getDefinitions();
    $definitions = $this->elementManager->getSortedDefinitions($definitions, 'category');
    $definitions = $this->elementManager->removeExcludeDefinitions($definitions);
    $grouped_definitions = $this->elementManager->getGroupedDefinitions($definitions);

    $sorted_definitions = [];
    foreach ($grouped_definitions as $grouped_definition) {
      $sorted_definitions += $grouped_definition;
    }
    foreach ($sorted_definitions as &$plugin_definition) {
      if (empty($plugin_definition['category'])) {
        $plugin_definition['category'] = $this->t('Other elements');
      }
    }
    return $sorted_definitions;
  }

}

<?php

namespace Drupal\openy_focal_point\Plugin\Field\FieldWidget;

use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint;
use Drupal\entity_browser\FieldWidgetDisplayManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the 'entity_reference' widget for entity browser.
 *
 * @FieldWidget(
 *   id = "openy_focal_point_entity_browser_entity_reference",
 *   label = @Translation("OpenY Entity browser"),
 *   description = @Translation("Uses entity browser to select entities. Pass paragraph type information down so it can be used by focal point"),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class OpenYFocalPointEntityReferenceBrowserWidget extends EntityReferenceBrowserWidget {


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['entity_browser_widget_paragraph_info'] = [
      '#type' => 'hidden',
      '#value' => $items->getEntity()->bundle(),
    ];

    return $element;
  }


}

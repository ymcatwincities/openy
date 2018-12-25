<?php

namespace Drupal\panelizer\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'panelizer' widget.
 *
 * @FieldWidget(
 *   id = "panelizer",
 *   label = @Translation("Panelizer"),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "panelizer"
 *   }
 * )
 */
class PanelizerWidget extends WidgetBase {

  /**
   * Returns the Panels display plugin manager.
   *
   * @return \Drupal\panels\PanelsDisplayManagerInterface
   */
  public function getPanelsManager() {
    // @todo: is it possible to inject this?
    return \Drupal::service('panels.display_manager');
  }

  /**
   * Returns the Panelizer entity plugin manager.
   *
   * @return \Drupal\panelizer\Plugin\PanelizerEntityManager
   */
  public function getPanelizerManager() {
    // @todo: is it possible to inject this?
    return \Drupal::service('plugin.manager.panelizer_entity');
  }

  /**
   * Returns the Panelizer service.
   *
   * @return \Drupal\panelizer\PanelizerInterface
   */
  public function getPanelizer() {
    // @todo: is it possible to inject this?
    return \Drupal::service('panelizer');
  }

  /**
   * @return \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  public function getEntityDisplayRepository() {
    // @todo: is it possible to inject this?
    return \Drupal::service('entity_display.repository');
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_view_modes = $this->getEntityDisplayRepository()->getViewModeOptionsByBundle($entity_type_id, $entity->bundle());

    // Get the current values from the entity.
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      $values[$item->view_mode] = [
        'default' => $item->default,
        'panels_display' => $item->panels_display,
      ];
    }

    // If any view modes are missing, then set the default.
    $displays = [];
    foreach ($entity_view_modes as $view_mode => $view_mode_info) {
      $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
      $displays[$view_mode] = $display->getThirdPartySetting('panelizer', 'displays', []);
      // If we don't have a value, or the default is __bundle_default__ and our
      // panels_display is empty, set the default to __bundle_default__.
      if (!isset($values[$view_mode]) || ($values[$view_mode]['default'] == '__bundle_default__' && empty($values[$view_mode]['panels_display']))) {
        if ($display->getThirdPartySetting('panelizer', 'enable', FALSE)) {
          $values[$view_mode] = [
            'default' => '__bundle_default__',
            'panels_display' => [],
          ];
        }
      }
    }

    // Add elements to the form for each view mode.
    $delta = 0;
    foreach ($values as $view_mode => $value) {
      $element[$delta]['view_mode'] = [
        '#type' => 'value',
        '#value' => $view_mode,
      ];

      $settings = $this->getPanelizer()->getPanelizerSettings($entity_type_id, $entity->bundle(), $view_mode);
      if (!empty($settings['allow'])) {
        // We default to this option when the user hasn't previous interacted
        // with the field.
        $options = [
          '__bundle_default__' => $this->t('Current default display'),
        ];
        foreach ($displays[$view_mode] as $machine_name => $panels_display) {
          $options[$machine_name] = $panels_display['label'];
        }
        $element[$delta]['default'] = [
          '#title' => $entity_view_modes[$view_mode],
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $value['default'],
        ];
        // If we have a value in panels_display, prevent the user from
        // interacting with the widget for the view modes that are overridden.
        if (!empty($value['panels_display'])) {
          $element[$delta]['default']['#disabled'] = TRUE;
          $element[$delta]['default']['#options'][$value['default']] = $this->t('Custom Override');
        }
      }
      else {
        $element[$delta]['default'] = [
          '#type' => 'value',
          '#value' => $value['default'],
        ];
      }

      $element[$delta]['panels_display'] = [
        '#type' => 'value',
        '#value' => $value['panels_display'],
      ];

      $delta++;
    }

    return $element;
  }

}

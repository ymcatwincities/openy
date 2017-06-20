<?php

namespace Drupal\paragraphs\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Default plugin implementation of the Entity Reference Selection plugin.
 *
 * @EntityReferenceSelection(
 *   id = "default:paragraph",
 *   label = @Translation("Paragraphs"),
 *   group = "default",
 *   entity_types = {"paragraph"},
 *   weight = 0
 * )
 */
class ParagraphSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->configuration['target_type'];
    $selection_handler_settings = $this->configuration['handler_settings'] ?: array();
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id);

    // Merge-in default values.
    $selection_handler_settings += array(
      'target_bundles' => array(),
      'target_bundles_drag_drop' => array(),
    );

    $bundle_options = array();
    $bundle_options_simple = array();

    // Default weight for new items.
    $weight = count($bundles) + 1;

    foreach ($bundles as $bundle_name => $bundle_info) {
      $bundle_options_simple[$bundle_name] = $bundle_info['label'];
      $bundle_options[$bundle_name] = array(
        'label' => $bundle_info['label'],
        'enabled' => isset($selection_handler_settings['target_bundles_drag_drop'][$bundle_name]['enabled']) ? $selection_handler_settings['target_bundles_drag_drop'][$bundle_name]['enabled'] : FALSE,
        'weight' => isset($selection_handler_settings['target_bundles_drag_drop'][$bundle_name]['weight']) ? $selection_handler_settings['target_bundles_drag_drop'][$bundle_name]['weight'] : $weight,
      );
      $weight++;
    }

    // Kept for compatibility with other entity reference widgets.
    $form['target_bundles'] = array(
      '#type' => 'checkboxes',
      '#options' => $bundle_options_simple,
      '#default_value' => isset($selection_handler_settings['target_bundles']) ? $selection_handler_settings['target_bundles'] : array(),
      '#access' => FALSE,
    );

    if ($bundle_options) {
      $form['target_bundles_drag_drop'] = [
        '#element_validate' => [[__CLASS__, 'targetTypeValidate']],
        '#type' => 'table',
        '#header' => [
          $this->t('Type'),
          $this->t('Weight'),
        ],
        '#attributes' => [
          'id' => 'bundles',
        ],
        '#prefix' => '<h5>' . $this->t('Paragraph types') . '</h5>',
        '#suffix' => '<div class="description">' . $this->t('The paragraph types that are allowed to be created in this field. Select none to allow all paragraph types.') .'</div>',
      ];

      $form['target_bundles_drag_drop']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'bundle-weight',
      ];
    }

    uasort($bundle_options, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    $weight_delta = $weight;

    // Default weight for new items.
    $weight = count($bundles) + 1;
    foreach ($bundle_options as $bundle_name => $bundle_info) {
      $form['target_bundles_drag_drop'][$bundle_name] = array(
        '#attributes' => array(
          'class' => array('draggable'),
        ),
      );

      $form['target_bundles_drag_drop'][$bundle_name]['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => $bundle_info['label'],
        '#title_display' => 'after',
        '#default_value' => $bundle_info['enabled'],
      );

      $form['target_bundles_drag_drop'][$bundle_name]['weight'] = array(
        '#type' => 'weight',
        '#default_value' => (int) $bundle_info['weight'],
        '#delta' => $weight_delta,
        '#title' => $this->t('Weight for type @type', array('@type' => $bundle_info['label'])),
        '#title_display' => 'invisible',
        '#attributes' => array(
          'class' => array('bundle-weight', 'bundle-weight-' . $bundle_name),
        ),
      );
      $weight++;
    }

    if (!count($bundle_options)) {
      $form['allowed_bundles_explain'] = [
        '#type' => 'markup',
        '#markup' => $this->t('You did not add any paragraph types yet, click <a href=":here">here</a> to add one.', [':here' => Url::fromRoute('paragraphs.type_add')->toString()]),
      ];
    }

    return $form;
  }

  /**
   * Validate helper to have support for other entity reference widgets.
   *
   * @param $element
   * @param FormStateInterface $form_state
   * @param $form
   */
  public static function targetTypeValidate($element, FormStateInterface $form_state, $form) {
    $values = &$form_state->getValues();
    $element_values = NestedArray::getValue($values, $element['#parents']);
    $bundle_options = array();

    if ($element_values) {
      $enabled = 0;
      foreach ($element_values as $machine_name => $bundle_info) {
        if (isset($bundle_info['enabled']) && $bundle_info['enabled']) {
          $bundle_options[$machine_name] = $machine_name;
          $enabled++;
        }
      }

      // All disabled = all enabled.
      if ($enabled === 0) {
        $bundle_options = NULL;
      }
    }

    // New value parents.
    $parents = array_merge(array_slice($element['#parents'], 0, -1), array('target_bundles'));
    NestedArray::setValue($values, $parents, $bundle_options);
  }
}

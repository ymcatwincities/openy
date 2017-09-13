<?php

namespace Drupal\views_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\views\Entity\View;
use Drupal\views\Views;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ViewsFieldFormatter.
 *
 * @FieldFormatter(
 *  id = "views_field_formatter",
 *  label = @Translation("View"),
 *  field_types = {
 *   "boolean",
 *   "changed",
 *   "comment",
 *   "computed",
 *   "created",
 *   "datetime",
 *   "decimal",
 *   "email",
 *   "entity_reference",
 *   "entity_reference_revisions",
 *   "expression_field",
 *   "file",
 *   "float",
 *   "image",
 *   "integer",
 *   "language",
 *   "link",
 *   "list_float",
 *   "list_integer",
 *   "list_string",
 *   "map",
 *   "path",
 *   "string",
 *   "string_long",
 *   "taxonomy_term_reference",
 *   "text",
 *   "text_long",
 *   "text_with_summary",
 *   "timestamp",
 *   "uri",
 *   "uuid"
 *   }
 * )
 */
class ViewsFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view' => '',
      'arguments' => [
        'field_value' => ['checked' => TRUE],
        'entity_id' => ['checked' => TRUE],
        'delta' => ['checked' => TRUE],
      ],
      'multiple' => FALSE,
      'implode_character' => '',
    ];
  }

  /**
   * Get the defaul Arguments.
   */
  protected function getDefaultArguments() {
    return [
      'field_value' => $this->t('Field value'),
      'entity_id' => $this->t('Entity ID'),
      'delta' => $this->t('Delta'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $options = array();
    foreach (Views::getAllViews() as $view) {
      foreach ($view->get('display') as $display) {
        $options[$view->get('label')][$view->get('id') . '::' . $display['id']] = sprintf('%s - %s', $view->get('label'), $display['display_title']);
      }
    }

    if (!empty($options)) {
      $element['view'] = array(
        '#title' => $this->t('View'),
        '#description' => $this->t("Select the view that will be displayed instead of the field's value"),
        '#type' => 'select',
        '#default_value' => $this->getSetting('view'),
        '#options' => $options,
      );

      $element['arguments'] = [
        '#type' => 'table',
        '#header' => [$this->t('View Arguments'), $this->t('Weight')],
        '#tabledrag' => [[
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'arguments-order-weight',
        ],
        ],
        '#caption' => $this->t('Select the arguments to send to the views, you can reorder them. These arguments can be used as contextual filters in the selected View.'),
      ];

      $default_arguments = array_keys(array_filter($this->getSetting('arguments'), function ($argument) {
        return $argument['checked'];
      }));

      $arguments = array_combine($default_arguments, $default_arguments);
      foreach ($this->getDefaultArguments() as $argument_id => $argument_name) {
        $arguments[$argument_id] = $argument_name;
      }
      foreach ($arguments as $argument_id => $argument_name) {
        $element['arguments'][$argument_id] = [
          'checked' => [
            '#type' => 'checkbox',
            '#title' => $argument_name,
            '#default_value' => in_array($argument_id, $default_arguments),
          ],
          'weight' => array(
            '#type' => 'weight',
            '#title' => $this->t('Weight for @title', ['@title' => $argument_name]),
            '#title_display' => 'invisible',
            '#attributes' => ['class' => ['arguments-order-weight']],
          ),
          '#attributes' => ['class' => ['draggable']],
        ];
      }

      $element['multiple'] = array(
        '#title' => $this->t('Multiple'),
        '#description' => $this->t('If the field is configured as multiple (<em>greater than one</em>), should we display a view per item ? If selected, there will be one view per item.'),
        '#type' => 'checkbox',
        '#default_value' => boolval($this->getSetting('multiple')),
      );
      $field_name = $this->fieldDefinition->getName();
      $element['implode_character'] = array(
        '#title' => $this->t('Implode with this character'),
        '#description' => $this->t('If it is set, all field values are imploded with this character (<em>ex: a simple comma</em>) and sent as one views argument. Empty to disable.'),
        '#type' => 'textfield',
        '#default_value' => $this->getSetting('implode_character'),
        '#states' => array(
          'visible' => array(
            ':input[name="fields[' . $field_name . '][settings_edit_form][settings][multiple]"]' => array('checked' => TRUE),
          ),
        ),
      );
    }
    else {
      $element['help'] = array(
        '#markup' => $this->t('<p>No available Views were found.</p>'),
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();
    list($view, $view_display) = explode('::', $settings['view']);
    $multiple = ((bool) $settings['multiple'] === TRUE) ? 'Enabled' : 'Disabled';

    $arguments = array_filter($settings['arguments'], function ($argument) {
      return $argument['checked'];
    });

    $all_arguments = $this->getDefaultArguments();
    $arguments = array_map(function ($argument) use ($all_arguments) {
      return $all_arguments[$argument];
    }, array_keys($arguments));

    if (empty($arguments)) {
      $arguments[] = $this->t('None');
    }

    if (isset($view)) {
      $summary[] = t('View: @view', ['@view' => $view]);
      $summary[] = t('Display: @display', ['@display' => $view_display]);
      $summary[] = t('Argument(s): @arguments', ['@arguments' => implode(', ', $arguments)]);
      $summary[] = t('Multiple: @multiple', ['@multiple' => $multiple]);
    }

    if ($multiple == 'Enabled') {
      if (!empty($settings['implode_character'])) {
        $summary[] = t('Implode character: @character', ['@character' => $settings['implode_character']]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();
    $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
    list($view_id, $view_display) = explode('::', $settings['view'], 2);

    $view = Views::getView($view_id);
    if (!$view || !$view->access($view_display)) {
      return $elements;
    }

    $view->setArguments($this->getArguments($items, $items[0], 0));
    $view->setDisplay($view_display);
    $view->preExecute();
    $view->execute();

    if (empty($view->result)) {
      return $elements;
    }

    $elements = array(
      '#cache' => array(
        'max-age' => 0,
      ),
    );

    if (((bool) $settings['multiple'] === TRUE) && ($cardinality != 1)) {
      if (!empty($settings['implode_character'])) {
        $elements[0] = [
          '#type' => 'view',
          '#name' => $view_id,
          '#display_id' => $view_display,
          '#arguments' => $this->getArguments($items, NULL, 0),
        ];
      }
      else {
        foreach ($items as $delta => $item) {
          $elements[$delta] = [
            '#type' => 'view',
            '#name' => $view_id,
            '#display_id' => $view_display,
            '#arguments' => $this->getArguments($items, $item, $delta),
          ];
        }
      }
    }
    else {
      $elements[0] = [
        '#type' => 'view',
        '#name' => $view_id,
        '#display_id' => $view_display,
        '#arguments' => $this->getArguments($items, $items[0], 0),
      ];
    }

    return $elements;
  }

  /**
   * Helper function. Returns the arguments to send to the views.
   */
  private function getArguments(FieldItemListInterface $items, $item, $delta) {
    $settings = $this->getSettings();

    $user_arguments = array_keys(array_filter($settings['arguments'], function ($argument) {
      return $argument['checked'];
    }));

    $arguments = [];
    foreach ($user_arguments as $argument) {
      switch ($argument) {
        case 'field_value':
          $columns = array_keys(
            $items->getFieldDefinition()->getFieldStorageDefinition()->getSchema()['columns']
          );
          $column = array_shift($columns);
          $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

          /** @var FieldItemInterface $item */
          if ($item) {
            $arguments[$argument] = !empty($column) && isset($item->getValue()[$column]) ? $item->getValue()[$column] : NULL;
          }

          if (((bool) $settings['multiple'] === TRUE) && ($cardinality != 1)) {
            if (!empty($settings['implode_character'])) {
              $values = [];

              /** @var FieldItemInterface $item */
              foreach ($items as $item) {
                $values[] = !empty($column) && isset($item->getValue()[$column]) ? $item->getValue()[$column] : NULL;
              }

              $arguments[$argument] = implode($settings['implode_character'], array_filter($values));
            }
          }
          break;

        case 'entity_id':
          $arguments[$argument] = $items->getParent()->getValue()->id();
          break;

        case 'delta':
          $arguments[$argument] = isset($delta) ? $delta : NULL;
          break;
      }
    }

    return array_values($arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    list($view_id) = explode('::', $this->getSetting('view'), 2);
    // Don't call the current view, as it would result into an
    // infinite recursion.
    // TODO: Check for infinite loop here.
    if ($view_id) {
      $view = View::load($view_id);
      $dependencies[$view->getConfigDependencyKey()][] = $view->getConfigDependencyName();
    }

    return $dependencies;
  }

}

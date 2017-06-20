<?php

namespace Drupal\paragraphs\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;
use Drupal\paragraphs;
use Symfony\Component\Validator\ConstraintViolationInterface;


/**
 * Plugin implementation of the 'entity_reference paragraphs' widget.
 *
 * We hide add / remove buttons when translating to avoid accidental loss of
 * data because these actions effect all languages.
 *
 * @FieldWidget(
 *   id = "entity_reference_paragraphs",
 *   label = @Translation("Paragraphs"),
 *   description = @Translation("An paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class InlineParagraphsWidget extends WidgetBase {

  /**
   * Indicates whether the current widget instance is in translation.
   *
   * @var bool
   */
  private $isTranslating;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'title' => t('Paragraph'),
      'title_plural' => t('Paragraphs'),
      'edit_mode' => 'open',
      'add_mode' => 'dropdown',
      'form_display_mode' => 'default',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = array();

    $elements['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Paragraph Title'),
      '#description' => $this->t('Label to appear as title on the button as "Add new [title]", this label is translatable'),
      '#default_value' => $this->getSetting('title'),
      '#required' => TRUE,
    );

    $elements['title_plural'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Plural Paragraph Title'),
      '#description' => $this->t('Title in its plural form.'),
      '#default_value' => $this->getSetting('title_plural'),
      '#required' => TRUE,
    );

    $elements['edit_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Edit mode'),
      '#description' => $this->t('The mode the paragraph is in by default. Preview will render the paragraph in the preview view mode.'),
      '#options' => array(
        'open' => $this->t('Open'),
        'closed' => $this->t('Closed'),
        'preview' => $this->t('Preview'),
      ),
      '#default_value' => $this->getSetting('edit_mode'),
      '#required' => TRUE,
    );

    $elements['add_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add mode'),
      '#description' => $this->t('The way to add new paragraphs.'),
      '#options' => array(
        'select' => $this->t('Select list'),
        'button' => $this->t('Buttons'),
        'dropdown' => $this->t('Dropdown button')
      ),
      '#default_value' => $this->getSetting('add_mode'),
      '#required' => TRUE,
    );

    $elements['form_display_mode'] = array(
      '#type' => 'select',
      '#options' => \Drupal::service('entity_display.repository')->getFormModeOptions($this->getFieldSetting('target_type')),
      '#description' => $this->t('The form display mode to use when rendering the paragraph form.'),
      '#title' => $this->t('Form display mode'),
      '#default_value' => $this->getSetting('form_display_mode'),
      '#required' => TRUE,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->t('Title: @title', array('@title' => $this->getSetting('title')));
    $summary[] = $this->t('Plural title: @title_plural', array('@title_plural' => $this->getSetting('title_plural')));

    switch($this->getSetting('edit_mode')) {
      case 'open':
      default:
        $edit_mode = $this->t('Open');
        break;
      case 'closed':
        $edit_mode = $this->t('Closed');
        break;
      case 'preview':
        $edit_mode = $this->t('Preview');
        break;
    }

    switch($this->getSetting('add_mode')) {
      case 'select':
      default:
        $add_mode = $this->t('Select list');
        break;
      case 'button':
        $add_mode = $this->t('Buttons');
        break;
      case 'dropdown':
        $add_mode = $this->t('Dropdown button');
        break;
    }

    $summary[] = $this->t('Edit mode: @edit_mode', array('@edit_mode' => $edit_mode));
    $summary[] = $this->t('Add mode: @add_mode', array('@add_mode' => $add_mode));
    $summary[] = $this->t('Form display mode: @form_display_mode', array('@form_display_mode' => $this->getSetting('form_display_mode')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\content_translation\Controller\ContentTranslationController::prepareTranslation()
   *   Uses a similar approach to populate a new translation.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];

    $paragraphs_entity = NULL;
    $host = $items->getEntity();
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    $entity_manager = \Drupal::entityTypeManager();
    $target_type = $this->getFieldSetting('target_type');

    $item_mode = isset($widget_state['paragraphs'][$delta]['mode']) ? $widget_state['paragraphs'][$delta]['mode'] : 'edit';
    $default_edit_mode = $this->getSetting('edit_mode');

    $show_must_be_saved_warning = FALSE;

    if (isset($widget_state['paragraphs'][$delta]['entity'])) {
      $paragraphs_entity = $widget_state['paragraphs'][$delta]['entity'];
    }
    elseif (isset($items[$delta]->entity)) {
      $paragraphs_entity = $items[$delta]->entity;

      // We don't have a widget state yet, get from selector settings.
      if (!isset($widget_state['paragraphs'][$delta]['mode'])) {

        if ($default_edit_mode == 'open') {
          $item_mode = 'edit';
        }
        elseif ($default_edit_mode == 'closed') {
          $item_mode = 'closed';
        }
        elseif ($default_edit_mode == 'preview') {
          $item_mode = 'preview';
        }
      }
    }
    elseif (isset($widget_state['selected_bundle'])) {

      $entity_type = $entity_manager->getDefinition($target_type);
      $bundle_key = $entity_type->getKey('bundle');

      $paragraphs_entity = $entity_manager->getStorage($target_type)->create(array(
        $bundle_key => $widget_state['selected_bundle'],
      ));

      $item_mode = 'edit';
    }

    if ($item_mode == 'collapsed') {
      $item_mode = $default_edit_mode;
      $show_must_be_saved_warning = TRUE;
    }

    if ($paragraphs_entity) {
      // Detect if we are translating.
      $this->initIsTranslating($form_state, $host);
      $langcode = $form_state->get('langcode');

      if (!$this->isTranslating) {
        // Set the langcode if we are not translating.
        if ($paragraphs_entity->get('langcode') != $langcode) {
          // If a translation in the given language already exists, switch to
          // that. If there is none yet, update the language.
          if ($paragraphs_entity->hasTranslation($langcode)) {
            $paragraphs_entity = $paragraphs_entity->getTranslation($langcode);
          }
          else {
            $paragraphs_entity->set('langcode', $langcode);
          }
        }
      }
      else {
        // Add translation if missing for the target language.
        if (!$paragraphs_entity->hasTranslation($langcode)) {
          // Get the selected translation of the paragraph entity.
          $entity_langcode = $paragraphs_entity->language()->getId();
          $source = $form_state->get(['content_translation', 'source']);
          $source_langcode = $source ? $source->getId() : $entity_langcode;
          $paragraphs_entity = $paragraphs_entity->getTranslation($source_langcode);
          // The paragraphs entity has no content translation source field if
          // no paragraph entity field is translatable, even if the host is.
          if ($paragraphs_entity->hasField('content_translation_source')) {
            // Initialise the translation with source language values.
            $paragraphs_entity->addTranslation($langcode, $paragraphs_entity->toArray());
            $translation = $paragraphs_entity->getTranslation($langcode);
            $manager = \Drupal::service('content_translation.manager');
            $manager->getTranslationMetadata($translation)->setSource($paragraphs_entity->language()->getId());
          }
        }
        // If any paragraphs type is translatable do not switch.
        if ($paragraphs_entity->hasField('content_translation_source')) {
          // Switch the paragraph to the translation.
          $paragraphs_entity = $paragraphs_entity->getTranslation($langcode);
        }
      }

      $element_parents = $parents;
      $element_parents[] = $field_name;
      $element_parents[] = $delta;
      $element_parents[] = 'subform';

      $id_prefix = implode('-', array_merge($parents, array($field_name, $delta)));
      $wrapper_id = Html::getUniqueId($id_prefix . '-item-wrapper');

      $element += array(
        '#type' => 'container',
        '#element_validate' => array(array($this, 'elementValidate')),
        'subform' => array(
          '#type' => 'container',
          '#parents' => $element_parents,
        ),
      );

      $element['#prefix'] = '<div id="' . $wrapper_id . '">';
      $element['#suffix'] = '</div>';

      $item_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($target_type);
      if (isset($item_bundles[$paragraphs_entity->bundle()])) {
        $bundle_info = $item_bundles[$paragraphs_entity->bundle()];

        $element['top'] = array(
          '#type' => 'container',
          '#weight' => -1000,
          '#attributes' => array(
            'class' => array(
              'paragraph-type-top',
            ),
          ),
        );

        $element['top']['paragraph_type_title'] = array(
          '#type' => 'container',
          '#weight' => 0,
          '#attributes' => array(
            'class' => array(
              'paragraph-type-title',
            ),
          ),
        );

        $element['top']['paragraph_type_title']['info'] = array(
          '#markup' => '<strong>Type: ' . $bundle_info['label'] . '</strong>',
        );

        $actions = array();
        $links = array();
        $info = array();

        if ($item_mode == 'edit') {

          if (isset($items[$delta]->entity) && ($default_edit_mode == 'preview' || $default_edit_mode == 'closed')) {
            $links['collapse_button'] = array(
              '#type' => 'submit',
              '#value' => $this->t('Collapse'),
              '#name' => strtr($id_prefix, '-', '_') . '_collapse',
              '#weight' => 499,
              '#submit' => array(array(get_class($this), 'paragraphsItemSubmit')),
              '#delta' => $delta,
              '#ajax' => array(
                'callback' => array(get_class($this), 'itemAjax'),
                'wrapper' => $widget_state['ajax_wrapper_id'],
                'effect' => 'fade',
              ),
              '#access' => $paragraphs_entity->access('update'),
              '#prefix' => '<li class="collapse">',
              '#suffix' => '</li>',
              '#paragraphs_mode' => 'collapsed',
            );
          }

          // Hide the button when translating.
          $button_access = $paragraphs_entity->access('delete') && !$this->isTranslating;
          $links['remove_button'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => strtr($id_prefix, '-', '_') . '_remove',
            '#weight' => 500,
            '#submit' => array(array(get_class($this), 'paragraphsItemSubmit')),
            '#limit_validation_errors' => array(array_merge($parents, array($field_name, 'add_more'))),
            '#delta' => $delta,
            '#ajax' => array(
              'callback' => array(get_class($this), 'itemAjax'),
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ),
            '#access' => $button_access,
            '#prefix' => '<li class="remove">',
            '#suffix' => '</li>',
            '#paragraphs_mode' => 'remove',
          );

          $info['edit_button_info'] = array(
            '#type' => 'markup',
            '#markup' => '<em>' . $this->t('You are not allowed to edit this @title.', array('@title' => $this->getSetting('title'))) . '</em>',
            '#access' => !$paragraphs_entity->access('update') && $paragraphs_entity->access('delete'),
          );

          $info['remove_button_info'] = array(
            '#type' => 'markup',
            '#markup' => '<em>' . $this->t('You are not allowed to remove this @title.', array('@title' => $this->getSetting('title'))) . '</em>',
            '#access' => !$paragraphs_entity->access('delete') && $paragraphs_entity->access('update'),
          );

          $info['edit_remove_button_info'] = array(
            '#type' => 'markup',
            '#markup' => '<em>' . $this->t('You are not allowed to edit or remove this @title.', array('@title' => $this->getSetting('title'))) . '</em>',
            '#access' => !$paragraphs_entity->access('update') && !$paragraphs_entity->access('delete'),
          );
        }
        elseif ($item_mode == 'preview' || $item_mode == 'closed') {
          $links['edit_button'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#name' => strtr($id_prefix, '-', '_') . '_edit',
            '#weight' => 501,
            '#submit' => array(array(get_class($this), 'paragraphsItemSubmit')),
            '#limit_validation_errors' => array(array_merge($parents, array($field_name, 'add_more'))),
            '#delta' => $delta,
            '#ajax' => array(
              'callback' => array(get_class($this), 'itemAjax'),
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ),
            '#access' => $paragraphs_entity->access('update'),
            '#prefix' => '<li class="edit">',
            '#suffix' => '</li>',
            '#paragraphs_mode' => 'edit',
          );

          $links['remove_button'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => strtr($id_prefix, '-', '_') . '_remove',
            '#weight' => 502,
            '#submit' => array(array(get_class($this), 'paragraphsItemSubmit')),
            '#limit_validation_errors' => array(array_merge($parents, array($field_name, 'add_more'))),
            '#delta' => $delta,
            '#ajax' => array(
              'callback' => array(get_class($this), 'itemAjax'),
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ),
            '#access' => $paragraphs_entity->access('delete'),
            '#prefix' => '<li class="remove">',
            '#suffix' => '</li>',
            '#paragraphs_mode' => 'remove',
          );

          if ($show_must_be_saved_warning) {
            $info['must_be_saved_info'] = array(
              '#type' => 'markup',
              '#markup' => '<em>' . $this->t('Warning: this content must be saved to reflect changes on this @title item.', array('@title' => $this->getSetting('title'))) . '</em>',
            );
          }

          $info['preview_info'] = array(
            '#type' => 'markup',
            '#markup' => '<em>' . $this->t('You are not allowed to view this @title.', array('@title' => $this->getSetting('title'))) . '</em>',
            '#access' => !$paragraphs_entity->access('view'),
          );

          $info['edit_button_info'] = array(
            '#type' => 'markup',
            '#markup' => '<em>' . $this->t('You are not allowed to edit this @title.', array('@title' => $this->getSetting('title'))) . '</em>',
            '#access' => !$paragraphs_entity->access('update') && $paragraphs_entity->access('delete'),
          );

          $info['remove_button_info'] = array(
            '#type' => 'markup',
            '#markup' => '<em>' . $this->t('You are not allowed to remove this @title.', array('@title' => $this->getSetting('title'))) . '</em>',
            '#access' => !$paragraphs_entity->access('delete') && $paragraphs_entity->access('update'),
          );

          $info['edit_remove_button_info'] = array(
            '#type' => 'markup',
            '#markup' => '<em>' . $this->t('You are not allowed to edit or remove this @title.', array('@title' => $this->getSetting('title'))) . '</em>',
            '#access' => !$paragraphs_entity->access('update') && !$paragraphs_entity->access('delete'),
          );
        }
        elseif ($item_mode == 'remove') {

          $element['top']['paragraph_type_title']['info'] = array(
            '#markup' => $this->t('Deleted @title: %type', ['@title' => $this->getSetting('title'), '%type' => $bundle_info['label']]),
          );

          $links['confirm_remove_button'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Confirm removal'),
            '#name' => strtr($id_prefix, '-', '_') . '_confirm_remove',
            '#weight' => 503,
            '#submit' => array(array(get_class($this), 'paragraphsItemSubmit')),
            '#limit_validation_errors' => array(array_merge($parents, array($field_name, 'add_more'))),
            '#delta' => $delta,
            '#ajax' => array(
              'callback' => array(get_class($this), 'itemAjax'),
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ),
            '#prefix' => '<li class="confirm-remove">',
            '#suffix' => '</li>',
            '#paragraphs_mode' => 'removed',
          );

          $links['restore_button'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Restore'),
            '#name' => strtr($id_prefix, '-', '_') . '_restore',
            '#weight' => 504,
            '#submit' => array(array(get_class($this), 'paragraphsItemSubmit')),
            '#limit_validation_errors' => array(array_merge($parents, array($field_name, 'add_more'))),
            '#delta' => $delta,
            '#ajax' => array(
              'callback' => array(get_class($this), 'itemAjax'),
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ),
            '#prefix' => '<li class="restore">',
            '#suffix' => '</li>',
            '#paragraphs_mode' => 'edit',
          );
        }

        if (count($links)) {
          $show_links = 0;
          foreach($links as $link_item) {
            if (!isset($link_item['#access']) || $link_item['#access']) {
              $show_links++;
            }
          }

          if ($show_links > 0) {

            $element['top']['links'] = $links;
            if ($show_links > 1) {
              $element['top']['links']['#theme_wrappers'] = array('dropbutton_wrapper', 'paragraphs_dropbutton_wrapper');
              $element['top']['links']['prefix'] = array(
                '#markup' => '<ul class="dropbutton">',
                '#weight' => -999,
              );
              $element['top']['links']['suffix'] = array(
                '#markup' => '</li>',
                '#weight' => 999,
              );
            }
            else {
              $element['top']['links']['#theme_wrappers'] = array('paragraphs_dropbutton_wrapper');
              foreach($links as $key => $link_item) {
                unset($element['top']['links'][$key]['#prefix']);
                unset($element['top']['links'][$key]['#suffix']);
              }
            }
            $element['top']['links']['#weight'] = 1;
          }
        }

        if (count($info)) {
          $show_info = FALSE;
          foreach($info as $info_item) {
            if (!isset($info_item['#access']) || $info_item['#access']) {
              $show_info = TRUE;
              break;
            }
          }

          if ($show_info) {
            $element['info'] = $info;
            $element['info']['#weight'] = 998;
          }
        }

        if (count($actions)) {
          $show_actions = FALSE;
          foreach($actions as $action_item) {
            if (!isset($action_item['#access']) || $action_item['#access']) {
              $show_actions = TRUE;
              break;
            }
          }

          if ($show_actions) {
            $element['actions'] = $actions;
            $element['actions']['#type'] = 'actions';
            $element['actions']['#weight'] = 999;
          }
        }
      }

      $display = EntityFormDisplay::collectRenderDisplay($paragraphs_entity, $this->getSetting('form_display_mode'));

      if ($item_mode == 'edit') {
        $display->buildForm($paragraphs_entity, $element['subform'], $form_state);
        foreach (Element::children($element['subform']) as $field) {
          if ($paragraphs_entity->hasField($field)) {
            $translatable = $paragraphs_entity->{$field}->getFieldDefinition()->isTranslatable();
            if ($translatable) {
              $element['subform'][$field]['widget']['#after_build'][] = [
                static::class,
                'removeTranslatabilityClue'
              ];
            }
          }
        }
      }
      elseif ($item_mode == 'preview') {
        $element['subform'] = array();
        $element['preview'] = entity_view($paragraphs_entity, 'preview', $paragraphs_entity->language()->getId());
        $element['preview']['#access'] = $paragraphs_entity->access('view');
      }
      elseif ($item_mode == 'closed') {
        $element['subform'] = array();
      }
      else {
        $element['subform'] = array();
      }

      $element['subform']['#attributes']['class'][] = 'paragraphs-subform';
      $element['subform']['#access'] = $paragraphs_entity->access('update');

      if ($item_mode == 'removed') {
        $element['#access'] = FALSE;
      }

      $widget_state['paragraphs'][$delta] = array(
        'entity' => $paragraphs_entity,
        'display' => $display,
        'mode' => $item_mode,
      );

      static::setWidgetState($parents, $field_name, $form_state, $widget_state);
    }
    else {
      $element['#access'] = FALSE;
    }

    return $element;
  }

  public function getAllowedTypes() {

    $return_bundles = array();

    $target_type = $this->getFieldSetting('target_type');
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($target_type);

    if ($this->getSelectionHandlerSetting('target_bundles') !== NULL) {
      $bundles = array_intersect_key($bundles, $this->getSelectionHandlerSetting('target_bundles'));
    }

    // Support for the paragraphs reference type.
    $drag_drop_settings = $this->getSelectionHandlerSetting('target_bundles_drag_drop');
    if ($drag_drop_settings) {
      $max_weight = count($bundles);

      foreach ($drag_drop_settings as $bundle_info) {
        if (isset($bundle_info['weight']) && $bundle_info['weight'] && $bundle_info['weight'] > $max_weight) {
          $max_weight = $bundle_info['weight'];
        }
      }

      // Default weight for new items.
      $weight = $max_weight + 1;
      foreach ($bundles as $machine_name => $bundle) {
        $return_bundles[$machine_name] = array(
          'label' => $bundle['label'],
          'weight' => isset($drag_drop_settings[$machine_name]['weight']) ? $drag_drop_settings[$machine_name]['weight'] : $weight,
        );
        $weight++;
      }
    }
    // Support for other reference types.
    else {
      $weight = 0;
      foreach ($bundles as $machine_name => $bundle) {
        if (!count($this->getSelectionHandlerSetting('target_bundles'))
          || in_array($machine_name, $this->getSelectionHandlerSetting('target_bundles'))) {

          $return_bundles[$machine_name] = array(
            'label' => $bundle['label'],
            'weight' => $weight,
          );

          $weight++;
        }
      }
    }

    uasort($return_bundles, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $return_bundles;
  }

  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);

    $max = $field_state['items_count'];
    $real_item_count = $max;
    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = array();
    $id_prefix = implode('-', array_merge($parents, array($field_name)));
    $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
    $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
    $elements['#suffix'] = '</div>';

    $field_state['ajax_wrapper_id'] = $wrapper_id;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    if ($max > 0) {
      for ($delta = 0; $delta < $max; $delta++) {

        // Add a new empty item if it doesn't exist yet at this delta.
        if (!isset($items[$delta])) {
          $items->appendItem();
        }

        // For multiple fields, title and description are handled by the wrapping
        // table.
        $element = array(
          '#title' => $is_multiple ? '' : $title,
          '#description' => $is_multiple ? '' : $description,
        );
        $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

        if ($element) {
          // Input field for the delta (drag-n-drop reordering).
          if ($is_multiple) {
            // We name the element '_weight' to avoid clashing with elements
            // defined by widget.
            $element['_weight'] = array(
              '#type' => 'weight',
              '#title' => $this->t('Weight for row @number', array('@number' => $delta + 1)),
              '#title_display' => 'invisible',
              // Note: this 'delta' is the FAPI #type 'weight' element's property.
              '#delta' => $max,
              '#default_value' => $items[$delta]->_weight ?: $delta,
              '#weight' => 100,
            );
          }

          if (isset($element['#access']) && !$element['#access']) {
            $real_item_count--;
          }
          else {
            $elements[$delta] = $element;
          }
        }
      }
    }

    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['real_item_count'] = $real_item_count;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $entity_manager = \Drupal::entityTypeManager();
    $target_type = $this->getFieldSetting('target_type');
    $bundles = $this->getAllowedTypes();
    $access_control_handler = $entity_manager->getAccessControlHandler($target_type);

    $options = array();
    $access_options = array();

    $dragdrop_settings = $this->getSelectionHandlerSetting('target_bundles_drag_drop');

    foreach ($bundles as $machine_name => $bundle) {

      if ($dragdrop_settings || (!count($this->getSelectionHandlerSetting('target_bundles'))
        || in_array($machine_name, $this->getSelectionHandlerSetting('target_bundles')))) {
        $options[$machine_name] = $bundle['label'];

        if ($access_control_handler->createAccess($machine_name)) {
          $access_options[$machine_name] = $bundle['label'];
        }
      }
    }

    if ($real_item_count > 0) {
      $elements += array(
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $is_multiple,
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max-1,
      );
    }
    else {
      $elements += [
        '#type' => 'container',
        '#theme_wrappers' => ['container'],
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => TRUE,
        '#max_delta' => $max-1,
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $title,
        ],
        'text' => [
          '#type' => 'container',
          'value' => [
            '#markup' => $this->t('No @title added yet.', ['@title' => $this->getSetting('title')]),
            '#prefix' => '<em>',
            '#suffix' => '</em>',
          ]
        ],
      ];

      if ($description) {
        $elements['description'] = [
          '#type' => 'container',
          'value' => ['#markup' => $description],
          '#attributes' => ['class' => ['description']],
        ];
      }
    }

    // Add 'add more' button, if not working with a programmed form.
    if (($real_item_count < $cardinality || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && !$form_state->isProgrammed()) {
      // Hide the button when translating.
      $host = $items->getEntity();
      $this->initIsTranslating($form_state, $host);
      $elements['add_more'] = array(
        '#type' => 'container',
        '#theme_wrappers' => array('paragraphs_dropbutton_wrapper'),
        '#access' => !$this->isTranslating,
      );

      if (count($access_options)) {
        if ($this->getSetting('add_mode') == 'button' || $this->getSetting('add_mode') == 'dropdown') {
          $drop_button = FALSE;
          if (count($access_options) > 1 && $this->getSetting('add_mode') == 'dropdown') {
            $drop_button = TRUE;
            $elements['add_more']['#theme_wrappers'] = array('dropbutton_wrapper');
            $elements['add_more']['prefix'] = array(
              '#markup' => '<ul class="dropbutton">',
              '#weight' => -999,
            );
            $elements['add_more']['suffix'] = array(
              '#markup' => '</ul>',
              '#weight' => 999,
            );
            $elements['add_more']['#suffix'] = $this->t(' to %type', array('%type' => $title));
          }
          foreach ($access_options as $machine_name => $label) {
            $elements['add_more']['add_more_button_' . $machine_name] = array(
              '#type' => 'submit',
              '#name' => strtr($id_prefix, '-', '_') . '_' . $machine_name . '_add_more',
              '#value' => $this->t('Add @type', array('@type' => $label)),
              '#attributes' => array('class' => array('field-add-more-submit')),
              '#limit_validation_errors' => array(array_merge($parents, array($field_name, 'add_more'))),
              '#submit' => array(array(get_class($this), 'addMoreSubmit')),
              '#ajax' => array(
                'callback' => array(get_class($this), 'addMoreAjax'),
                'wrapper' => $wrapper_id,
                'effect' => 'fade',
              ),
              '#bundle_machine_name' => $machine_name,
            );
            if ($drop_button) {
              $elements['add_more']['add_more_button_' . $machine_name]['#prefix'] = '<li>';
              $elements['add_more']['add_more_button_' . $machine_name]['#suffix'] = '<li>';
            }
          }
        }
        else {
          $elements['add_more']['add_more_select'] = array(
            '#type'    => 'select',
            '#options' => $options,
            '#title'   => $this->t('@title type', array('@title' => $this->getSetting('title'))),
            '#label_display' => 'hidden',
          );

          $text = $this->t('Add @title', array('@title' => $this->getSetting('title')));

          if ($real_item_count > 0) {
            $text = $this->t('Add another @title', array('@title' => $this->getSetting('title')));
          }

          $elements['add_more']['add_more_button'] = array(
            '#type' => 'submit',
            '#name' => strtr($id_prefix, '-', '_') . '_add_more',
            '#value' => $text,
            '#attributes' => array('class' => array('field-add-more-submit')),
            '#limit_validation_errors' => array(array_merge($parents, array($field_name, 'add_more'))),
            '#submit' => array(array(get_class($this), 'addMoreSubmit')),
            '#ajax' => array(
              'callback' => array(get_class($this), 'addMoreAjax'),
              'wrapper' => $wrapper_id,
              'effect' => 'fade',
            ),
          );
          $elements['add_more']['add_more_button']['#suffix'] = $this->t(' to %type', array('%type' => $title));
        }
      }
      else {
        if (count($options)) {
          $elements['add_more']['info'] = array(
            '#type' => 'markup',
            '#markup' => '<em>' . $this->t('You are not allowed to add any of the @title types.', array('@title' => $this->getSetting('title'))) . '</em>',
          );
        }
        else {
          $elements['add_more']['info'] = array(
            '#type' => 'markup',
            '#markup' => '<em>' . $this->t('You did not add any @title types yet.', array('@title' => $this->getSetting('title'))) . '</em>',
          );
        }
      }
    }

    $elements['#attached']['library'][] = 'paragraphs/drupal.paragraphs.admin';

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $parents = $form['#parents'];

    // Identify the manage field settings default value form.
    if (in_array('default_value_input', $parents, TRUE)) {
      // Since the entity is not reusable neither cloneable, having a default
      // value is not supported.
      return ['#markup' => $this->t('No widget available for: %label.', ['%label' => $items->getFieldDefinition()->getLabel()])];
    }

    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * Gets current language code from the form state or item.
   *
   * Since the paragraph field is not set as translatable, the item language
   * code is set to the source language. The intended translation language
   * is only accessibly through the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   * @return string
   */
  protected function getCurrentLangcode(FormStateInterface $form_state, FieldItemListInterface $items) {
    return $form_state->get('langcode') ?: $items->getEntity()->language()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    if ($widget_state['real_item_count'] < $element['#cardinality'] || $element['#cardinality'] == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $widget_state['items_count']++;
    }

    if (isset($button['#bundle_machine_name'])) {
      $widget_state['selected_bundle'] = $button['#bundle_machine_name'];
    }
    else {
      $widget_state['selected_bundle'] = $element['add_more']['add_more_select']['#value'];
    }

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    $form_state->setRebuild();
  }

  public static function paragraphsItemSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    $delta = array_slice($button['#array_parents'], -4, -3);
    $delta = $delta[0];

    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    $widget_state['paragraphs'][$delta]['mode'] = $button['#paragraphs_mode'];

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    $form_state->setRebuild();
  }

  public static function itemAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
    $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element;
  }

  /**
   * Returns the value of a setting for the entity reference selection handler.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSelectionHandlerSetting($setting_name) {
    $settings = $this->getFieldSetting('handler_settings');
    return isset($settings[$setting_name]) ? $settings[$setting_name] : NULL;
  }

  /**
   * Checks whether a content entity is referenced.
   *
   * @return bool
   */
  protected function isContentReferenced() {
    $target_type = $this->getFieldSetting('target_type');
    $target_type_info = \Drupal::entityTypeManager()->getDefinition($target_type);
    return $target_type_info->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface');
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidate($element, FormStateInterface $form_state, $form) {
    $field_name = $this->fieldDefinition->getName();
    $widget_state = static::getWidgetState($element['#field_parents'], $field_name, $form_state);
    $delta = $element['#delta'];

    if (isset($widget_state['paragraphs'][$delta]['entity'])) {
      $entity = $widget_state['paragraphs'][$delta]['entity'];

      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
      $display = $widget_state['paragraphs'][$delta]['display'];

      if ($widget_state['paragraphs'][$delta]['mode'] == 'edit') {
        // Extract the form values on submit for getting the current paragraph.
        $display->extractFormValues($entity, $element['subform'], $form_state);
        $display->validateFormValues($entity, $element['subform'], $form_state);
      }
    }

    static::setWidgetState($element['#field_parents'], $field_name, $form_state, $widget_state);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->getEntity();
    $field_name = $this->fieldDefinition->getName();
    $widget_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $element = NestedArray::getValue($form_state->getCompleteForm(), $widget_state['array_parents']);

    $new_revision = FALSE;
    if ($entity instanceof RevisionableInterface) {
      if ($entity->isNewRevision()) {
        $new_revision = TRUE;
      }
      // Most of the time we don't know yet if the host entity is going to be
      // saved as a new revision using RevisionableInterface::isNewRevision().
      // Most entity types (at least nodes) however use a boolean property named
      // "revision" to indicate whether a new revision should be saved. Use that
      // property.
      elseif ($entity->getEntityType()->hasKey('revision') && $form_state->getValue('revision')) {
        $new_revision = TRUE;
      }
    }

    foreach ($values as $delta => &$item) {
      if (isset($widget_state['paragraphs'][$item['_original_delta']]['entity'])
        && $widget_state['paragraphs'][$item['_original_delta']]['mode'] != 'remove') {
        $paragraphs_entity = $widget_state['paragraphs'][$item['_original_delta']]['entity'];


        /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
        $display =  $widget_state['paragraphs'][$item['_original_delta']]['display'];
        $display->extractFormValues($paragraphs_entity, $element[$item['_original_delta']]['subform'], $form_state);

        $paragraphs_entity->setNewRevision($new_revision);
        // A content entity form saves without any rebuild. It needs to set the
        // language to update it in case of language change.
        if ($paragraphs_entity->get('langcode') != $form_state->get('langcode')) {
          // If a translation in the given language already exists, switch to
          // that. If there is none yet, update the language.
          if ($paragraphs_entity->hasTranslation($form_state->get('langcode'))) {
            $paragraphs_entity = $paragraphs_entity->getTranslation($form_state->get('langcode'));
          }
          else {
            $paragraphs_entity->set('langcode', $form_state->get('langcode'));
          }
        }
        $paragraphs_entity->setNeedsSave(TRUE);
        $item['entity'] = $paragraphs_entity;
        $item['target_id'] = $paragraphs_entity->id();
        $item['target_revision_id'] = $paragraphs_entity->getRevisionId();
      }
      // If our mode is remove don't save or reference this entity.
      // @todo: Maybe we should actually delete it here?
      elseif($widget_state['paragraphs'][$item['_original_delta']]['mode'] == 'remove' || $widget_state['paragraphs'][$item['_original_delta']]['mode'] == 'removed') {
        $item['target_id'] = NULL;
        $item['target_revision_id'] = NULL;
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    // Filter possible empty items.
    $items->filterEmptyItems();
    return parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * Initializes the translation form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\Core\Entity\EntityInterface $host
   */
  protected function initIsTranslating(FormStateInterface $form_state, EntityInterface $host) {
    if ($this->isTranslating != NULL) {
      return;
    }
    $this->isTranslating = FALSE;
    if (!$host->isTranslatable()) {
      return;
    }
    if (!$host->getEntityType()->hasKey('default_langcode')) {
      return;
    }
    $default_langcode_key = $host->getEntityType()->getKey('default_langcode');
    if (!$host->hasField($default_langcode_key)) {
      return;
    }

    if (!empty($form_state->get('content_translation'))) {
      // Adding a language through the ContentTranslationController.
      $this->isTranslating = TRUE;
    }
    if ($host->hasTranslation($form_state->get('langcode')) && $host->getTranslation($form_state->get('langcode'))->get($default_langcode_key)->value == 0) {
      // Editing a translation.
      $this->isTranslating = TRUE;
    }
  }

  /**
   * After-build callback for removing the translatability clue from the widget.
   *
   * If the fields on the paragraph type are translatable,
   * ContentTranslationHandler::addTranslatabilityClue()adds an
   * "(all languages)" suffix to the widget title. That suffix is incorrect and
   * is being removed by this method using a #after_build on the field widget.
   *
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function removeTranslatabilityClue(array $element, FormStateInterface $form_state) {
    // Widgets could have multiple elements with their own titles, so remove the
    // suffix if it exists, do not recurse lower than this to avoid going into
    // nested paragraphs or similar nested field types.
    $suffix = ' <span class="translation-entity-all-languages">(' . t('all languages') . ')</span>';
    if (isset($element['#title']) && strpos($element['#title'], $suffix)) {
      $element['#title'] = str_replace($suffix, '', $element['#title']);
    }
    // Loop over all widget deltas.
    foreach (Element::children($element) as $delta) {
      if (isset($element[$delta]['#title']) && strpos($element[$delta]['#title'], $suffix)) {
        $element[$delta]['#title'] = str_replace($suffix, '', $element[$delta]['#title']);
      }
      // Loop over all form elements within the current delta.
      foreach (Element::children($element[$delta]) as $field) {
        if (isset($element[$delta][$field]['#title']) && strpos($element[$delta][$field]['#title'], $suffix)) {
          $element[$delta][$field]['#title'] = str_replace($suffix, '', $element[$delta][$field]['#title']);
        }
      }
    }
    return $element;
  }
}

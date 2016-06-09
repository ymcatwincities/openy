<?php
/**
 * @file
 * Contains \Drupal\file_browser\Plugin\Field\FieldWidget\FileBrowser.
 *
 * This code is copied from the file_entity module, as we needed this
 * functionality but did not want to make a hard dependency on File Entity.
 *
 * All credit goes to Berdir (berdir on Drupal.org).
 *
 * @see https://github.com/drupal-media/file_entity/commit/af4131334c88a7e7bd045874070c79b9af7d842d
 */

namespace Drupal\file_browser\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReference;

/**
 * Entity browser file widget.
 *
 * @FieldWidget(
 *   id = "file_browser",
 *   label = @Translation("File Browser"),
 *   provider = "file_browser",
 *   multiple_values = TRUE,
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileBrowser extends EntityReference {

  /**
   * Due to the table structure, this widget has a different depth.
   *
   * @var int
   */
  protected static $deleteDepth = 3;

  /**
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $items;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['view_mode'] = 'thumbnail';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['field_widget_display']['#access'] = FALSE;
    $element['field_widget_display_settings']['#access'] = FALSE;

    $element['view_mode'] = [
      '#title' => t('File view mode'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('view_mode'),
      '#options' => \Drupal::service('entity_display.repository')->getViewModeOptions('file'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $entity_browser_id = $this->getSetting('entity_browser');
    $view_mode = $this->getSetting('view_mode');

    if (empty($entity_browser_id)) {
      return [t('No entity browser selected.')];
    }
    else {
      $browser = $this->entityManager->getStorage('entity_browser')
        ->load($entity_browser_id);
      $summary[] = t('Entity browser: @browser', ['@browser' => $browser->label()]);
    }

    if (!empty($view_mode)) {
      $summary[] = t('View mode: @name', ['@name' => $view_mode]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $this->items = $items;
    return parent::formElement($items, $delta, $element, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function displayCurrentSelection($details_id, $field_parents, $entities) {

    $view_mode = $this->getSetting('view_mode');

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('file');
    $config = \Drupal::config('file.settings');

    $delta = 0;

    $order_class = $this->fieldDefinition->getName() . '-delta-order';

    $current = [
      '#type' => 'table',
      '#header' => [$this->t('Preview'), $this->t('Metadata'), ['data' => $this->t('Operations'), 'colspan' => 2], t('Order', array(), array('context' => 'Sort order'))],
      '#empty' => $this->t('No files yet'),
      '#attributes' => ['class' => ['entities-list']],
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $order_class,
        ),
      ),
    ];
    foreach ($entities as $entity) {
      if ($this->fieldDefinition->getType() == 'image' && $view_mode == 'default') {
        $image = \Drupal::service('image.factory')->get($entity->getFileUri());
        if ($image->isValid()) {
          $width = $image->getWidth();
          $height = $image->getHeight();
        }
        else {
          $width = $height = NULL;
        }

        $display = array(
          '#weight' => -10,
          '#theme' => 'image_style',
          '#width' => $width,
          '#height' => $height,
          '#style_name' => 'thumbnail',
          '#uri' => $entity->getFileUri(),
        );
      }
      else {
        $display = $view_builder->view($entity, $view_mode);
      }

      // Find the default description.
      $description = '';
      $alt = '';
      $title = '';
      $weight = $delta;
      foreach ($this->items as $item) {
        if ($item->target_id == $entity->id()) {
          if ($this->fieldDefinition->getType() == 'file') {
            $description = $item->description;
          }
          elseif ($this->fieldDefinition->getType() == 'image') {
            $alt = $item->alt;
            $title = $item->title;
          }
          $weight = $item->_weight ?: $delta;
        }
      }

      $current[$entity->id()] = [
        '#attributes' => [
          'class' => ['draggable'],
          'data-entity-id' => $entity->id()
        ],
        'display' => $display,
        'meta' => [
          'description' => [
            '#type' => $config->get('description.type'),
            '#title' =>$this->t('Description'),
            '#default_value' => $description,
            '#maxlength' => $config->get('description.length'),
            '#description' =>$this->t('The description may be used as the label of the link to the file.'),
            '#access' => $this->fieldDefinition->getType() == 'file' && $this->fieldDefinition->getSetting('description_field'),
          ],
          'alt' => [
            '#type' => 'textfield',
            '#title' =>$this->t('Alternative text'),
            '#default_value' => $alt,
            '#maxlength' => 512,
            '#description' =>$this->t('This text will be used by screen readers, search engines, or when the image cannot be loaded.'),
            '#access' => $this->fieldDefinition->getType() == 'image' && $this->fieldDefinition->getSetting('alt_field'),
            '#required' => $this->fieldDefinition->getType() == 'image' && $this->fieldDefinition->getSetting('alt_field_required'),
          ],
          'title' => [
            '#type' => 'textfield',
            '#title' =>$this->t('Title'),
            '#default_value' => $title,
            '#maxlength' => 1024,
            '#description' =>$this->t('The title is used as a tool tip when the user hovers the mouse over the image.'),
            '#access' => $this->fieldDefinition->getType() == 'image' && $this->fieldDefinition->getSetting('title_field'),
            '#required' => $this->fieldDefinition->getType() == 'image' && $this->fieldDefinition->getSetting('title_field_required'),
          ],
        ],
        'edit_button' => [
          '#type' => 'submit',
          '#value' => $this->t('Edit'),
          '#ajax' => [
            'url' => Url::fromRoute('entity_browser.edit_form', ['entity_type' => $entity->getEntityTypeId(), 'entity' => $entity->id()])
          ],
          // @todo Investigate why this doesn't work.
          //'#access' => (bool) $this->getSetting('field_widget_edit')
          '#access' => FALSE,
        ],
        'remove_button' => [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#ajax' => [
            'callback' => [get_class($this), 'updateWidgetCallback'],
            'wrapper' => $details_id,
          ],
          '#submit' => [[get_class($this), 'removeItemSubmit']],
          '#name' => $this->fieldDefinition->getName() . '_remove_' . $entity->id(),
          '#limit_validation_errors' => [array_merge($field_parents, [$this->fieldDefinition->getName()])],
          '#attributes' => ['data-entity-id' => $entity->id()],
          '#access' => (bool) $this->getSetting('field_widget_remove')
        ],
        '_weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for row @number', array('@number' => $delta + 1)),
          '#title_display' => 'invisible',
          // Note: this 'delta' is the FAPI #type 'weight' element's property.
          '#delta' => count($entities),
          '#default_value' => $weight,
          '#attributes' => ['class' => array($order_class)],
        ],
      ];

      $delta++;
    }

    return $current;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $ids = empty($values['target_id']) ? [] : explode(' ', trim($values['target_id']));
    $return = [];
    foreach ($ids as $id) {
      $item_values = [
        'target_id' => $id,
        '_weight' => $values['current'][$id]['_weight'],
      ];
      if ($this->fieldDefinition->getType() == 'file' && isset($values['current'][$id]['meta']['description'])) {
        $item_values['description'] = $values['current'][$id]['meta']['description'];
      }
      if ($this->fieldDefinition->getType() == 'image' && isset($values['current'][$id]['meta']['alt'])) {
        $item_values['alt'] = $values['current'][$id]['meta']['alt'];
      }
      if ($this->fieldDefinition->getType() == 'image' && isset($values['current'][$id]['meta']['title'])) {
        $item_values['title'] = $values['current'][$id]['meta']['title'];
      }
      $return[] = $item_values;
    }

    // Return ourself as the structure doesn't match the default.
    usort($return, function ($a, $b) {
      return SortArray::sortByKeyInt($a, $b, '_weight');
    });

    return array_values($return);
  }

}

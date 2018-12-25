<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\Field\FieldWidget\VideoEmbedWidget.
 */

namespace Drupal\video\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

use Drupal\Core\Field\WidgetBase;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SortArray;

use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'video_embed' widget.
 *
 * @FieldWidget(
 *   id = "video_embed",
 *   label = @Translation("Video Embed"),
 *   field_types = {
 *     "video"
 *   }
 * )
 */
class VideoEmbedWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = array(
      'file_directory' => 'video-thumbnails/[date:custom:Y]-[date:custom:m]',
      'allowed_providers' => ["youtube" => "youtube"],
      'uri_scheme' => 'public'
    );
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element =  array();
    $settings = $this->getSettings();
    $provider_manager = \Drupal::service('video.provider_manager');
    $element['allowed_providers'] = [
      '#title' => t('Video Providers'),
      '#type' => 'checkboxes',
      '#default_value' => $this->getSetting('allowed_providers'),
      '#options' => $provider_manager->getProvidersOptionList(),
    ];
    $element['file_directory'] = array(
      '#type' => 'textfield',
      '#title' => t('Thumbnail directory'),
      '#default_value' => $settings['file_directory'],
      '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
      '#element_validate' => array(array(get_class($this), 'validateDirectory')),
      '#weight' => 3,
    );
    $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $element['uri_scheme'] = array(
      '#type' => 'radios',
      '#title' => t('Thumbnail destination'),
      '#options' => $scheme_options,
      '#default_value' => $this->getSetting('uri_scheme'),
      '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
      '#weight' => 6,
    );
    return $element;
  }
  
  /**
   * Form API callback
   *
   * Removes slashes from the beginning and end of the destination value and
   * ensures that the file directory path is not included at the beginning of the
   * value.
   *
   * This function is assigned as an #element_validate callback in
   * settingsForm().
   */
  public static function validateDirectory($element, FormStateInterface $form_state) {
    // Strip slashes from the beginning and end of $element['file_directory'].
    $value = trim($element['#value'], '\\/');
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Providers : @allowed_providers<br/>Thumbnail directory : @file_directory', 
    array(
      '@allowed_providers' => implode(', ', array_filter($this->getSetting('allowed_providers'))),
      '@file_directory' => $this->getSetting('uri_scheme') . '://' . $this->getSetting('file_directory'),
    ));
    return $summary;
  }
  
  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

    // Load the items for form rebuilds from the field state as they might not
    // be in $form_state->getValues() because of validation limitations. Also,
    // they are only passed in as $items when editing existing entities.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    if (isset($field_state['items'])) {
      $items->setValue($field_state['items']);
    }

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = array();

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

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
            '#default_value' => $items[$delta]->_weight ? : $delta,
            '#weight' => 100,
          );
        }

        $elements[$delta] = $element;
      }
    }

    if ($elements) {
      $elements += array(
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      );

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, array($field_name)));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        // $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        // $elements['#suffix'] = '</div>';

        $elements['add_more'] = array(
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => array('class' => array('field-add-more-submit')),
          '#limit_validation_errors' => array(array_merge($parents, array($field_name))),
          '#submit' => array(array(get_class($this), 'addMoreSubmit')),
          '#ajax' => array(
            'callback' => array(get_class($this), 'addMoreAjax'),
            'effect' => 'fade',
          ),
          '#weight' => 1000
        );
      }
    }

    if ($is_multiple) {
      // The group of elements all-together need some extra functionality after
      // building up the full list (like draggable table rows).
      $elements['#file_upload_delta'] = $delta;
      $elements['#process'] = array(array(get_class($this), 'processMultiple'));
      $elements['#field_name'] = $field_name;
      $elements['#language'] = $items->getLangcode();

    }
    return $elements;
  }
  
  /**
   * Form API callback: Processes a group of file_generic field elements.
   *
   * Adds the weight field to each row so it can be ordered and adds a new Ajax
   * wrapper around the entire group so it can be replaced all at once.
   *
   * This method on is assigned as a #process callback in formMultipleElements()
   * method.
   */
  public static function processMultiple($element, FormStateInterface $form_state, $form) {
    $element_children = Element::children($element, TRUE);
    $count = count($element_children);

    // Count the number of already uploaded files, in order to display new
    // items in \Drupal\file\Element\ManagedFile::uploadAjaxCallback().
    if (!$form_state->isRebuilding()) {
      $count_items_before = 0;
      foreach ($element_children as $children) {
        if (!empty($element[$children]['#default_value']['fids'])) {
          $count_items_before++;
        }
      }

      $form_state->set('file_upload_delta_initial', $count_items_before);
    }

    foreach ($element_children as $delta => $key) {
      if ($delta != $element['#file_upload_delta']) {
        $description = static::getDescriptionFromElement($element[$key]);
        $element[$key]['_weight'] = array(
          '#type' => 'weight',
          '#title' => $description ? t('Weight for @title', array('@title' => $description)) : t('Weight for new file'),
          '#title_display' => 'invisible',
          '#delta' => $count,
          '#default_value' => $delta,
        );
      }
      else {
        // The title needs to be assigned to the upload field so that validation
        // errors include the correct widget label.
        $element[$key]['#title'] = $element['#title'];
        $element[$key]['_weight'] = array(
          '#type' => 'hidden',
          '#default_value' => $delta,
        );
      }
    }

    // Add a new wrapper around all the elements for Ajax replacement.
    $element['#prefix'] = '<div id="' . $element['#id'] . '-ajax-wrapper">';
    $element['#suffix'] = '</div>';
    $element['add_more']['#ajax']['wrapper'] = $element['#id'] . '-ajax-wrapper';

    return $element;
  }
  
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if(empty($items[$delta]->getValue())){
      $element['value'] =  $element + array(
        '#type' => 'textfield',
        '#attributes' => ['class' => ['js-text-full', 'text-full']],
        '#element_validate' => [
          [get_class($this), 'validateFormElement'],
        ],
        '#allowed_providers' => $this->getSetting('allowed_providers')
      );
    }
    else {
      $element += parent::formElement($items, $delta, $element, $form, $form_state);
    }
    
    return $element;
  }
  
  /**
   * Form API callback: Processes a file_generic field element.
   *
   * Expands the file_generic type to include the description and display
   * fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);
    $item = $element['#value'];
    $element['data']['#value'] = $item['data'];
    $element['data']['#type'] = 'hidden';
    return $element;
   }
  
  /**
   * Form element validation handler for URL alias form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (empty($value)) {
      return;
    }
    $provider_manager = \Drupal::service('video.provider_manager');
    $enabled_providers = $provider_manager->loadDefinitionsFromOptionList($element['#allowed_providers']);
    if (!$provider_manager->loadApplicableDefinitionMatches($enabled_providers, $value)) {
      $form_state->setError($element, t('Could not find a video provider to handle the given URL.'));
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], array($field_name));
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);
    if ($key_exists) {
      // Account for drag-and-drop reordering if needed.
      if (!$this->handlesMultipleValues()) {
        // Remove the 'value' of the 'add more' button.
        unset($values['add_more']);

        // The original delta, before drag-and-drop reordering, is needed to
        // route errors to the correct form element.
        foreach ($values as $delta => &$value) {
          $value['_original_delta'] = $delta;
        }

        usort($values, function($a, $b) {
          return SortArray::sortByKeyInt($a, $b, '_weight');
        });
      }
      // Let the widget massage the submitted values.
      foreach($values as $delta => &$value){
        if(!empty($value['value']) && empty($value['fids'])){
          // ready to save the file
          $provider_manager = \Drupal::service('video.provider_manager');
          $allowed_providers = $this->getSetting('allowed_providers');
          $enabled_providers = $provider_manager->loadDefinitionsFromOptionList($allowed_providers);
          if ($provider_matches = $provider_manager->loadApplicableDefinitionMatches($enabled_providers, $value['value'])) {
            $definition  = $provider_matches['definition'];
            $matches = $provider_matches['matches'];
            $uri = $definition['stream_wrapper'] . '://' . $matches['id'];
            
            $storage = \Drupal::entityManager()->getStorage('file');
            $results = $storage->getQuery()
                    ->condition('uri', $uri)
                    ->execute();
            if(!(count($results) > 0)){
              $user = \Drupal::currentUser();
              $file = File::Create([
                'uri' => $uri,
                'filemime' => $definition['mimetype'],
                'filesize' => 1,
                'uid' => $user->id()
                ]);
              $file->save();
              unset($values[$delta]);
              $values[] = array('fids' => array($file->id()), 'data' => serialize($matches));
            }
            else {
              unset($values[$delta]);
              $values[] = array('fids' => array(reset($results)), 'data' => serialize($matches));
            }
          }
        }
      }
      $values = $this->massageFormValues($values, $form, $form_state);      
      // Assign the values and remove the empty ones.
      $items->setValue($values);
      $items->filterEmptyItems();

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = isset($item->_original_delta) ? $item->_original_delta : $delta;
        unset($item->_original_delta, $item->_weight);
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }
}

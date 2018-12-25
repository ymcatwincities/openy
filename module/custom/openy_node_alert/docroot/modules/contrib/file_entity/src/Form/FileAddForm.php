<?php

namespace Drupal\file_entity\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileType;
use Drupal\file_entity\UploadValidatorsTrait;

/**
 * Form controller for file type forms.
 */
class FileAddForm extends FormBase {

  use UploadValidatorsTrait;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'file_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $options = array()) {
    $step = in_array($form_state->get('step'), array(1, 2, 3, 4)) ? $form_state->get('step') : 1;
    $form_state->set('step', $step);
    $form_state->set('options', $options);

    switch ($step) {
      case 1:
        return $this->stepUpload($form, $form_state, $options);

      case 2:
        return $this->stepFileType($form, $form_state);

      case 3:
        return $this->stepScheme($form, $form_state);

      case 4:
        return $this->stepFields($form, $form_state);

    }

    return FALSE;
  }

  /**
   * Step 1
   * Generate form fields for the first step in the add file wizard.
   *
   * @param array $form
   *   Holds form data
   * @param FormStateInterface $form_state
   *   Holds form state data
   * @return array
   *   Returns form data
   */
  function stepUpload(array $form, FormStateInterface $form_state) {
    $options = [
      'file_extensions' => \Drupal::config('file_entity.settings')
        ->get('default_allowed_extensions'),
    ];
    $options = $form_state->get('options') ? $form_state->get('options') : $options;
    $validators = $this->getUploadValidators($options);

    $form['upload'] = array(
      '#type' => 'managed_file',
      '#title' => t('Upload a new file'),
      '#upload_location' => $this->getUploadDestinationUri($form_state->get('options')),
      '#upload_validators' => $validators,
      '#progress_indicator' => 'bar',
      '#required' => TRUE,
      '#default_value' => $form_state->has('file') ? array($form_state->get('file')->id()) : NULL,
    );

    $file_upload_help = array(
      '#theme' => 'file_upload_help',
      '#upload_validators' => $form['upload']['#upload_validators'],
    );
    $form['upload']['#description'] = drupal_render($file_upload_help);

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['next'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Next'),
    );

    return $form;
  }

  /**
   * Determines the upload location for the file add upload form.
   *
   * @param array $params
   *   An array of parameters from the media browser.
   * @param array $data
   *   (optional) An array of token objects to pass to token_replace().
   *
   * @return string
   *   A file directory URI with tokens replaced.
   *
   * @see token_replace()
   */
  function getUploadDestinationUri(array $params, array $data = array()) {
    $params += array(
      'uri_scheme' => file_default_scheme(),
      'file_directory' => '',
    );

    $destination = trim($params['file_directory'], '/');

    // Replace tokens.
    $destination = \Drupal::token()->replace($destination, $data);

    return $params['uri_scheme'] . '://' . $destination;
  }

  /**
   * Form Step 2
   * Select file types.
   *
   * Skipped if there is only one file type known for the uploaded file.
   *
   * @param $form
   * @param $form_state
   */
  function stepFileType(array $form, FormStateInterface $form_state) {
    $file = $form_state->get('file');

    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('File type'),
      '#options' => $this->getCandidateFileTypes($file),
      '#default_value' => $form_state->get('type'),
      '#required' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['previous'] = array(
      '#type' => 'submit',
      '#value' => t('Previous'),
      '#limit_validation_errors' => array(),
    );

    $form['actions']['next'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Next'),
    );

    return $form;
  }

  /**
   * Get the candidate filetypes for a given file.
   *
   * Only filetypes for which the user has access to create entities are returned.
   *
   * @param \Drupal\file\FileInterface $file
   *   An upload file from form_state.
   *
   * @return array
   *   An array of file type bundles that support the file's mime type.
   */
  function getCandidateFileTypes(FileInterface $file) {
    $types = \Drupal::moduleHandler()->invokeAll('file_type', array($file));
    \Drupal::moduleHandler()->alter('file_type', $types, $file);
    $candidates = array();
    foreach ($types as $type) {

      if ($has_access = \Drupal::entityManager()->getAccessControlHandler('file')
        ->createAccess($type)
      ) {
        $candidates[$type] = FileType::load($type)->label();
      }
    }

    return $candidates;
  }

  /**
   * Form Step 3
   *
   * @param $form
   * @param $form_state
   * @return mixed
   */
  function stepScheme(array $form, FormStateInterface $form_state) {
    $options = array();
    foreach (\Drupal::service('stream_wrapper_manager')->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE) as $scheme => $description) {
      $options[$scheme] = SafeMarkup::checkPlain($description);
    }

    $form['scheme'] = array(
      '#type' => 'radios',
      '#title' => t('Destination'),
      '#options' => $options,
      '#default_value' => $form_state->get('scheme') ?: file_default_scheme(),
      '#required' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['previous'] = array(
      '#type' => 'submit',
      '#value' => t('Previous'),
      '#limit_validation_errors' => array(),
    );
    $form['actions']['next'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Next'),
    );

    return $form;
  }

  /**
   * Step 4
   *
   * @param $form
   * @param $form_state
   */
  function stepFields(array $form, FormStateInterface $form_state) {

    // Load the file and overwrite the filetype set on the previous screen.
    /** @var \Drupal\file\FileInterface$file*/
    $file = $form_state->get('file');

    $form_state->set('form_display', EntityFormDisplay::collectRenderDisplay($file, 'default'));
    $form_state->get('form_display')->buildForm($file, $form, $form_state);


    $form['actions'] = array('#type' => 'actions');
    $form['actions']['previous'] = array(
      '#type' => 'submit',
      '#value' => t('Previous'),
      '#limit_validation_errors' => array(),
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->get('step') == 4) {
      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
      $form_display = $form_state->get('form_display');
      $form_display->extractFormValues($form_state->get('file'), $form, $form_state);
      $form_display->validateFormValues($form_state->get('file'), $form, $form_state);
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This var is set to TRUE when we are ready to save the file.
    $save = FALSE;
    $trigger = $form_state->getTriggeringElement()['#id'];
    $current_step = $form_state->get('step');

    // Store select values in $form_state.
    foreach (array('type', 'scheme') as $key) {
      if ($form_state->hasValue($key)) {
        $form_state->set($key, $form_state->getValue($key));
      }
    }

    $steps_to_check = array(2, 3);
    if ($trigger == 'edit-previous') {
      // If the previous button was hit,
      // the step checking order should be reversed 3, 2.
      $steps_to_check = array_reverse($steps_to_check);
    }

    /* @var \Drupal\file\FileInterface $file */

    if ($form_state->has('file')) {
      $file = $form_state->get('file');
    }
    else {
      $file = File::load($form_state->getValue(array('upload', 0)));
      $form_state->set('file', $file);
    }

    foreach ($steps_to_check as $step) {
      // Check if we can skip step 2 and 3.
      if (($current_step == $step - 1 && $trigger == 'edit-next') || ($current_step == $step + 1 && $trigger == 'edit-previous')) {

        if ($step == 2) {
          // Check if we can skip step 2.
          $candidates = $this->getCandidateFileTypes($file);
          if (count($candidates) == 1) {
            $candidates_keys = array_keys($candidates);
            // There is only one possible filetype for this file.
            // Skip the second page.
            $current_step += ($trigger == 'edit-previous') ? -1 : 1;
            $form_state->set('type', reset($candidates_keys));
          }
          elseif (\Drupal::config('file_entity.settings')->get('wizard_skip_file_type')) {
            // Do not assign the file a file type.
            $current_step += ($trigger == 'edit-previous') ? -1 : 1;
            $form_state->set('type', FILE_TYPE_NONE);
          }
        }
        else {
          // Check if we can skip step 3.
          $schemes = \Drupal::service('stream_wrapper_manager')->getWrappers(StreamWrapperInterface::WRITE_VISIBLE);

          if (!$file->isWritable()) {
            // The file is read-only (remote) and must use its provided scheme.
            $current_step += ($trigger == 'edit-previous') ? -1 : 1;
            $form_state->set('scheme', file_uri_scheme($file->getFileUri()));
          }
          elseif (count($schemes) == 1) {
            // There is only one possible stream wrapper for this file.
            // Skip the third page.
            $current_step += ($trigger == 'edit-previous') ? -1 : 1;
            $form_state->set('scheme', key($schemes));
          }
          elseif (\Drupal::config('file_entity.settings')->get('wizard_skip_scheme')) {
            // Assign the file the default scheme.
            $current_step += ($trigger == 'edit-previous') ? -1 : 1;
            $form_state->set('scheme', file_default_scheme());
          }
        }
      }
    }

    // We have the filetype, check if we can skip step 4.
    if (($current_step == 3 && $trigger == 'edit-next')) {
      $file->updateBundle($form_state->get('type'));

      $save = TRUE;

      foreach ($file->getFieldDefinitions() as $field_definition) {
        if ($field_definition instanceof FieldConfigInterface) {
          // This filetype does have configurable fields, do not save as we
          // do step 4 first.
          $save = FALSE;
          break;
        }
      }

      if ($this->config('file_entity.settings')->get('wizard_skip_fields', FALSE)) {
        // Save the file with blanks fields.
        $save = TRUE;
      }

    }


    // Form id's can vary depending on how many other forms are displayed, so we
    // need to do string comparissons. e.g edit-submit--2.
    if (strpos($trigger, 'edit-next') !== FALSE) {
      $current_step++;
    }
    elseif (strpos($trigger, 'edit-previous') !== FALSE) {
      $current_step--;
    }
    elseif (strpos($trigger, 'edit-submit') !== FALSE) {
      $save = TRUE;
    }

    $form_state->set('step', $current_step);

    if ($save) {
      if (file_uri_scheme($file->getFileUri()) != $form_state->get('scheme')) {
        // @TODO: Users should not be allowed to create private files without permission ('view private files')
        if ($moved_file = file_move($file, $form_state->get('scheme') . '://' . file_uri_target($file->getFileUri()), FILE_EXISTS_RENAME)) {
          // Only re-assign the file object if file_move() did not fail.
          $moved_file->setFilename($file->getFilename());

          $file = $moved_file;
        }
      }
      $file->display = TRUE;

      // Change the file from temporary to permanent.
      $file->status = FILE_STATUS_PERMANENT;

      // Save entity
      $file->save();

      $form_state->set('file', $file);

      drupal_set_message(t('@type %name was uploaded.', array(
        '@type' => $file->type->entity->label(),
        '%name' => $file->getFilename()
      )));

      // Figure out destination.
      if (\Drupal::currentUser()->hasPermission('administer files')) {
        $form_state->setRedirect('entity.file.collection');
      }
      else {
        $form_state->setRedirectUrl($file->urlInfo());
      }
    }
    else {
      $form_state->setRebuild();
    }

  }
}

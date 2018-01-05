<?php

namespace Drupal\file_entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file_entity\Entity\FileType;
use Drupal\file_entity\Mimetypes;

/**
 * Form controller for file type forms.
 */
class FileTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var FileType $type */
    $type = $this->entity;

    $form['label'] = array(
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => t('The human-readable name of the file type.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        'exists' => 'Drupal\file_entity\Entity\FileType::load',
        'source' => array('label'),
      ),
      '#description' => t('A unique machine-readable name for this file type. It must only contain lowercase letters, numbers, and underscores.'),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => t('A brief description of this file type.'),
    );

    $form['mimetypes'] = array(
      '#type' => 'textarea',
      '#title' => t('MIME types'),
      '#description' => t('Enter one MIME type per line.'),
      '#default_value' => implode("\n", $type->getMimeTypes()),
    );

    $mimetypes = new Mimetypes(\Drupal::moduleHandler());

    $form['mimetype_list'] = array(
      '#type' => 'details',
      '#title' => t('Known MIME types'),
      '#collapsed' => TRUE,
    );
    $form['mimetype_list']['list'] = array(
      '#theme' => 'item_list',
      '#items' => $mimetypes->get(),
    );

    $form['actions'] = array('#type' => 'actions');

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    // Arbitrary expressions in empty() allowed in PHP 5.5 only.
    $id = $type->id();
    if (!empty($id)) {
      $form['actions']['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setError($form['id'], $this->t("Invalid machine-readable name. Enter a name other than %invalid.", array('%invalid' => $id)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();

    $t_args = array('%name' => $this->entity->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The file type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The file type %name has been added.', $t_args));
      \Drupal::logger('file_entity')->notice(t('Added file type %name.', $t_args));
    }

    $form_state->setRedirect('entity.file_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // Convert multi-line string to array before copying.
    // This may be called multiple times and exectued only if it is a string.
    if (is_string($form_state->getValue('mimetypes'))) {
      $form_state->setValue('mimetypes', explode("\n", str_replace("\r", "", $form_state->getValue('mimetypes'))));
    }
    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }
}

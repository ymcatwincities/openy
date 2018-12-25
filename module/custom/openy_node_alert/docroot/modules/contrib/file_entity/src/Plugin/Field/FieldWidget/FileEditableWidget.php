<?php

namespace Drupal\file_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * File widget with support for editing the referenced file inline.
 *
 * @FieldWidget(
 *   id = "file_editable",
 *   label = @Translation("Editable file"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileEditableWidget extends FileWidget {

  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    if (!$element['#files']) {
      return $element;
    }

    foreach ($element['#files'] as $fid => $file) {
      /** @var \Drupal\file\FileInterface $file */
      $element['edit_button'] = [
        '#name' => "file_editable_$fid",
        '#type' => 'submit',
        '#value' => t('Edit'),
        '#ajax' => [
          'url' => Url::fromRoute('entity.file.inline_edit_form', ['file' => $fid]),
        ],
        '#access' => $file->access('update'),
      ];
    }

    return $element;
  }

}

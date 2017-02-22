<?php

namespace Drupal\file_entity\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Form class for inline edit form.
 */
class FileInlineEditForm extends FileEditForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Point form submissions to the Ajax controller.
    $form['#action'] = '/file/' . $this->getEntity()->id() . '/inline-edit';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $elements = parent::actionsElement($form, $form_state);
    // Let's allow the save button only.
    foreach (Element::children($elements) as $key) {
      if ($key != 'submit') {
        $elements[$key]['#access'] = FALSE;
      }
    }
    // Use Ajax.
    $elements['submit']['#ajax'] = [
      'url' => Url::fromRoute('entity.file.inline_edit_form', ['file' => $this->getEntity()->id()]),
    ];
    return $elements;
  }

}

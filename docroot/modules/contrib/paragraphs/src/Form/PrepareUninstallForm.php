<?php

namespace Drupal\paragraphs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Removes fields and data used by Paragraphs.
 */
class PrepareUninstallForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paragraphs_admin_settings_prepare_uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['paragraphs'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Prepare uninstall'),
      '#description' => $this->t('Clicking on this button, all Paragraphs data will be removed.'),
    );

    $form['paragraphs']['prepare_uninstall'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete Paragraphs data'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'title' => $this->t('Deleting paragraphs'),
      'operations' => [
        [
          [__CLASS__, 'deleteParagraphs'], [],
        ],
      ],
      'progress_message' => static::t('Deleting Paragraphs data... Completed @percentage% (@current of @total).'),
    ];
    batch_set($batch);

    drupal_set_message($this->t('Paragraphs data has been deleted.'));
  }

  /**
   * Deletes Paragraphs datas.
   */
  public static function deleteParagraphs(&$context) {
    $paragraph_ids = \Drupal::entityQuery('paragraph')->range(0, 100)->execute();
    $storage = \Drupal::entityTypeManager()->getStorage('paragraph');
    if ($paragraphs = $storage->loadMultiple($paragraph_ids)) {
      $storage->delete($paragraphs);
    }
    $context['finished'] = (int) count($paragraph_ids) < 100;
  }

}

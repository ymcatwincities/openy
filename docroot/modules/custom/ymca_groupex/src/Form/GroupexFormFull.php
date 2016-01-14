<?php
/**
 * @file
 * Contains \Drupal\ymca_groupex\Form\GroupexFormFull
 */

namespace Drupal\ymca_groupex\Form;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements Groupex Full Form.
 */
class GroupexFormFull extends GroupexFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groupex_form_full';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}

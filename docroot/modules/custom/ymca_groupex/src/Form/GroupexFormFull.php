<?php
/**
 * @file
 * Contains \Drupal\ymca_groupex\Form\GroupexFormFull.
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

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['location'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getOptions($this->request(['query' => ['locations' => TRUE]]), 'id', 'name'),
      '#title' => $this->t('Locations'),
      '#weight' => -100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'ymca_groupex.all_schedules_search_results',
      ['query' => $this->getRedirectParams($form, $form_state)]
    );
  }

}

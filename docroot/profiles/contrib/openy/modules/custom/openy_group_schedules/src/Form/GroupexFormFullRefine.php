<?php

namespace Drupal\openy_group_schedules\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\openy_group_schedules\GroupexScheduleFetcher;

/**
 * Implements GroupEx Pro Full Refine Form.
 */
class GroupexFormFullRefine extends GroupexFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groupex_form_full_refine';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check if we have additional argument to prepopulate the form.
    $refine = FALSE;
    $params = [];
    $args = func_get_args();
    if (isset($args[2])) {
      $refine = TRUE;
      $params = GroupexScheduleFetcher::normalizeParameters($args[2]);
    }

    $form = parent::buildForm($form, $form_state, $params);

    $form['location'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getOptions($this->request(['query' => ['locations' => TRUE]]), 'id', 'name'),
      '#title' => $this->t('Location'),
      '#title_extra' => $this->t('(optional—select up to 4)'),
      '#weight' => -100,
      '#default_value' => ($refine && !empty($params['location'])) ? $params['location'] : [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'openy_group_schedules.all_schedules_search_results',
      [],
      ['query' => $this->getRedirectParams($form, $form_state)]
    );
  }

}

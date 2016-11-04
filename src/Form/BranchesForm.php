<?php

namespace Drupal\ygs_popups\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Contribute form.
 */
class BranchesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ygs_popups_branches_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $destination = '') {
    $form['destination'] = array('#type' => 'value', '#value' => $destination);

    $branches_list = $this->getBranchesList();

    $form['branch'] = array(
      '#type' => 'radios',
      '#title' => t('Please select a location'),
      '#default_value' => key($branches_list),
      '#options' => $branches_list,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Set location'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $destination = UrlHelper::parse($form_state->getValue('destination'));
    $destination['path'] = str_replace(base_path(), '/', $destination['path']);
    $branch = $form_state->getValue('branch');
    $destination['query']['location'] = $branch;
    $uri = \Drupal::request()->getUriForPath($destination['path']);
    $response = new RedirectResponse($uri . '?' . UrlHelper::buildQuery($destination['query']));
    $response->send();
  }

  /**
   * Get Branches list.
   */
  public function getBranchesList() {
    $db = \Drupal::database();
    $query = $db->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('type', 'branch');
    $query->condition('status', 1);
    $branches_list = $query->execute()->fetchAllKeyed();
    return $branches_list;
  }

}

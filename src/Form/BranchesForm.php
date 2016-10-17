<?php
/**
 * @file
 * Contains \Drupal\ygs_popups\Form\BranchesForm.
 */

namespace Drupal\ygs_popups\Form;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Contribute form.
 */
class BranchesForm extends FormBase {

  const EXPIRE_TIME = '+ 365 day';

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
    // Get Branches list.
    $db = \Drupal::database();
    $query = $db->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('type', 'branch');
    $query->condition('status', 1);
    $branches_list = $query->execute()->fetchAllKeyed();

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
    $branch = $form_state->getValue('branch');
    $destination['query']['location'] = $branch;
    $uri = \Drupal::request()->getUriForPath($destination['path']);
    setcookie('ygs_preferred_branch', $branch, strtotime(self::EXPIRE_TIME), base_path());
    $response = new RedirectResponse($uri . '?' . UrlHelper::buildQuery($destination['query']));
    $response->send();
  }

}

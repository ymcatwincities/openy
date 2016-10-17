<?php
/**
 * @file
 * Contains \Drupal\ygs_popups\Form\ClassBranchesForm.
 */

namespace Drupal\ygs_popups\Form;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Contribute form.
 */
class ClassBranchesForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ygs_popups_class_branches_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL, $destination = '') {
    $form['destination'] = array('#type' => 'value', '#value' => $destination);
    $branches_list = $this->getBranchesList($node);

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
  public function getBranchesList($node) {
    $branches_list = array();
    if ($node) {
      // Get sessions for current class.
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'session')
        ->condition('status', 1)
        ->condition('field_class.target_id', $node->id());
      $class_sessions = $query->execute();
      $sessions = \Drupal\node\Entity\Node::loadMultiple($class_sessions);


      foreach ($sessions as $session) {
        // Get Branches list for sessions with current class.
        $branches = $session->get('field_location')->referencedEntities();
        foreach ($branches as $branch) {
          if (!isset($branches_list[$branch->id()])) {
            $branches_list[$branch->id()] = $branch->title->value;
          }
        }
      }
    }
    return $branches_list;
  }

}

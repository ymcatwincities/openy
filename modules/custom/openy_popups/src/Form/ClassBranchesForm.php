<?php

namespace Drupal\openy_popups\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\NodeInterface;

/**
 * Contribute form.
 */
class ClassBranchesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_popups_class_branches_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL, $destination = '') {
    $form['destination'] = ['#type' => 'value', '#value' => $destination];
    $branches_list = $this->getBranchesList($node);
    $default = !empty($branches_list['branch']) ? key($branches_list['branch']) : 0;
    if (!$default) {
      $default = !empty($branches_list['camp']) ? key($branches_list['camp']) : 0;
    }

    $form['branch'] = [
      '#type' => 'radios',
      '#prefix' => '<div class="fieldgroup form-item form-wrapper"><h2 class="fieldset-legend">' . t('Please select a location') . '</h2><div class="fieldset-wrapper">',
      '#suffix' => '</div></div>',
      '#default_value' => $default,
      '#options' => ['all' => 'All'] + $branches_list['branch'] + $branches_list['camp'],
      '#all' => ['all' => 'All'],
      '#branches' => $branches_list['branch'],
      '#camps' => $branches_list['camp'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Set location'),
    ];
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
    unset($destination['query']['session']);
    $uri = \Drupal::request()->getUriForPath($destination['path']);
    $response = new RedirectResponse($uri . '?' . UrlHelper::buildQuery($destination['query']));

    $response->send();
  }

  /**
   * Get Branches list.
   */
  public static function getBranchesList(NodeInterface $node) {
    $branches_list = [
      'branch' => [],
      'camp' => [],
    ];

    if (!\Drupal::hasService('session_instance.manager')) {
      return $branches_list;
    }

    $locations = \Drupal::service('session_instance.manager')
      ->getLocationsByClassNode($node);
    foreach ($locations as $location) {
      if (!isset($branches_list[$location->bundle()][$location->id()])) {
        $branches_list[$location->bundle()][$location->id()] = $location->title->value;
      }
    }

    return $branches_list;
  }

}

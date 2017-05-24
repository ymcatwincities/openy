<?php

namespace Drupal\openy_popups\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\NodeInterface;
use \Drupal\openy_session_instance\SessionInstanceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contribute form.
 */
class ClassBranchesForm extends FormBase {

  /**
   * The SessionInstanceManager.
   *
   * @var \Drupal\openy_session_instance\SessionInstanceManagerInterface
   */
  protected $sessionInstanceManager;

  /**
   * Creates a new BranchSessionsForm.
   *
   * @param SessionInstanceManagerInterface $session_instance_manager
   *   The SessionInstanceManager.
   */
  public function __construct(SessionInstanceManagerInterface $session_instance_manager) {
    $this->sessionInstanceManager = $session_instance_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('session_instance.manager'));
  }

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
    $branches_list = $this->getBranchesList($node, $this->sessionInstanceManager);
    $default = !empty($branches_list['branch']) ? key($branches_list['branch']) : 0;
    if (!$default) {
      $default = !empty($branches_list['camp']) ? key($branches_list['camp']) : 0;
    }

    $form['branch'] = [
      '#type' => 'radios',
      '#title' => t('Please select a location'),
      '#default_value' => $default,
      '#options' => $branches_list['branch'] + $branches_list['camp'],
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
    $uri = \Drupal::request()->getUriForPath($destination['path']);
    $response = new RedirectResponse($uri . '?' . UrlHelper::buildQuery($destination['query']));
    $response->send();
  }

  /**
   * Get Branches list.
   */
  public static function getBranchesList(NodeInterface $node, SessionInstanceManagerInterface $session_instance_manager) {
    $branches_list = [
      'branch' => [],
      'camp' => [],
    ];

    $locations = $session_instance_manager->getLocationsByClassNode($node);
    foreach ($locations as $location) {
      if (!isset($branches_list[$location->bundle()][$location->id()])) {
        $branches_list[$location->bundle()][$location->id()] = $location->title->value;
      }
    }

    return $branches_list;
  }

}

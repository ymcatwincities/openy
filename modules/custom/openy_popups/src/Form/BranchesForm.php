<?php

namespace Drupal\openy_popups\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;


/**
 * Contribute form.
 */
class BranchesForm extends FormBase {

  /**
   * The ID provided from query.
   *
   * @var int
   */
  protected $nodeId;

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new BranchSessionsForm.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $query = parent::getRequest();
    $parameters = $query->query->all();
    if (!empty($parameters['node'])) {
      $this->nodeId = $parameters['node'];
    }
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_popups_branches_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $destination = '') {

    $form['destination'] = ['#type' => 'value', '#value' => $destination];

    $branches_list = $this->getBranchesList();

    $config = \Drupal::config('openy_popups.settings');

    $default = $config->get('location');

    $form['branch'] = self::buildBranch($default, $branches_list);
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Set location'),
    ];
    return $form;
  }

  public static function buildBranch($default = 'All', $branches_list) {
    return [
      '#type' => 'radios',
      '#prefix' => '<div class="fieldgroup form-item form-wrapper"><h2 class="fieldset-legend">' . t('Please select a location') . '</h2><div class="fieldset-wrapper">',
      '#suffix' => '</div></div>',
      '#default_value' => $default,
      '#options' => ['All' => 'All'] + $branches_list['branch'] + $branches_list['camp'],
      '#all' => ['All' => 'All'],
      '#branches' => $branches_list['branch'],
      '#camps' => $branches_list['camp'],
    ];
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
    $response->headers->setCookie(new Cookie('openy_preferred_branch', $branch, strtotime('+1 day'), '/', null, false, false));
    $response->send();
  }

  /**
   * Get Branches list.
   */
  public function getBranchesList() {
    $locations_to_be_displayed = [];
    $db = \Drupal::database();
    if (!empty($this->nodeId) && $node = $this->entityTypeManager->getStorage('node')->load($this->nodeId)) {
      $query = $db->select('node_field_data', 'n');
      // Field on Session that has Class.
      $query->innerJoin('node__field_session_class', 'fc', 'n.nid = fc.entity_id');
      $query->innerJoin('node_field_data', 'fdc', 'fc.field_session_class_target_id = fdc.nid');
      $query->condition('fdc.status', 1);
      // Field on Class that has Activity.
      $query->innerJoin('node__field_class_activity', 'fa', 'fc.field_session_class_target_id = fa.entity_id');
      $query->innerJoin('node_field_data', 'fda', 'fa.field_class_activity_target_id = fda.nid');
      $query->condition('fda.status', 1);
      // Field on Activity that has Programs Subcategory
      $query->innerJoin('node__field_activity_category', 'fps', 'fa.field_class_activity_target_id = fps.entity_id');
      $query->innerJoin('node_field_data', 'fds', 'fa.field_class_activity_target_id = fda.nid');
      // Field on Session that has Location.
      $query->innerJoin('node__field_session_location', 'fl', 'n.nid = fl.entity_id');
      $query->fields('fl', ['field_session_location_target_id']);
      $query->condition('n.type', 'session');
      $query->condition('fps.field_activity_category_target_id', $this->nodeId);
      $query->condition('n.status', 1);
      $query->groupBy('fl.field_session_location_target_id');
      $items = $query->execute()->fetchAll();

      foreach ($items as $item) {
        $locations_to_be_displayed[$item->field_session_location_target_id] = $item->field_session_location_target_id;
      }
    }
    return self::getLocations($locations_to_be_displayed);

  }

  /**
   * Get Locations list.
   */
  public static function getLocations($locations_to_be_displayed = null) {
    $db = \Drupal::database();
    $branches_list = [
      'branch' => [],
      'camp' => [],
    ];
    $query = $db->select('node_field_data', 'n')
      ->fields('n', ['nid', 'title', 'type'])
      ->condition('type', ['branch', 'camp'], 'IN')
      ->condition('status', 1);
    $items = $query->execute()->fetchAll();
    foreach ($items as $item) {
      // By default we show all locations instead of showing empty popup.
      if (!empty($locations_to_be_displayed) && !in_array($item->nid, $locations_to_be_displayed)) {
        continue;
      }
      $branches_list[$item->type][$item->nid] = $item->title;
    }

    return $branches_list;
  }

}

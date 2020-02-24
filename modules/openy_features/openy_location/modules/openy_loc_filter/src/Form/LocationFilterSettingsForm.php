<?php

namespace Drupal\openy_loc_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Cache\Cache;

/**
* Location Filter settings form.
*/
class LocationFilterSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'openy_loc_filter.location_filter_settings';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new LocationFilterSettingsForm.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(Connection $database) {
    $this->connection = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'openy_loc_filter_location_filter_settings';
  }

  /**
  * {@inheritdoc}
  */
  protected function getEditableConfigNames() {
    return [
      self::CONFIG_NAME,
    ];
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $selected_locations = $config->get('locations') ? $config->get('locations') : [];

    $branches_list = $this->getBranchesList();
    $locations = $branches_list['branch'] + $branches_list['camp'];
    if (count($selected_locations) == count($locations)) {
      $selected_locations['All'] = 'All';
    }

    $form['locations'] = [
      '#type' => 'checkboxes',
      '#prefix' => '<div class="fieldgroup form-item form-wrapper"><h2 class="fieldset-legend">' . $this->t('Select locations available for Location filters') . '</h2><div class="fieldset-wrapper">',
      '#suffix' => '</div></div>',
      '#default_value' => $selected_locations,
      '#options' => ['All' => 'All'] + $locations,
      '#all' => ['All' => 'All'],
      '#branches' => $branches_list['branch'],
      '#camps' => $branches_list['camp'],
      '#description' => $this->t('All locations are not selected = All locations are selected.'),
    ];

    $form['#attached']['library'][] = 'openy_loc_filter/openy_location_filter';

    return parent::buildForm($form, $form_state);
  }

  /**
  * Get Branches list.
  *
  * @return array
  *   Array of Branch and Camp node id's.
  */
  public function getBranchesList() {
    $branches_list = [
      'branch' => [],
      'camp' => [],
    ];

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->connection->select('node_field_data', 'n')
      ->fields('n', ['nid', 'title', 'type'])
      ->condition('type', ['branch', 'camp'], 'IN')
      ->condition('status', NodeInterface::PUBLISHED);
    $items = $query->execute()->fetchAll();
    foreach ($items as $item) {
      $branches_list[$item->type][$item->nid] = $item->title;
    }

    return $branches_list;
  }

  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config(self::CONFIG_NAME);
    $locations = $form_state->getValue('locations');
    $locations = array_filter($locations, function ($value, $key) {
      return $key !== 'All' && !empty($value);
    }, ARRAY_FILTER_USE_BOTH);
    $config->set('locations', $locations)->save();
    Cache::invalidateTags(['config:core.entity_view_display.node.program_subcategory.default']);

    parent::submitForm($form, $form_state);
  }

}

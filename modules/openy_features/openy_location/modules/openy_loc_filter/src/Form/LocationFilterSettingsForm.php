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
    $config_locations = $this->config(self::CONFIG_NAME)->get('locations');
    $selected_locations = $config_locations ? $config_locations : [];

    $branches_list = $this->getBranchesList();
    $locations = $branches_list['branch'] + $branches_list['camp'];
    if (count($selected_locations) == count($locations)) {
      $selected_locations['All'] = $this->t('All');
    }

    $form['description'] = [
      '#type'=>'markup',
      '#markup' => $this->t('On this page, you can limit the list of branches and camps,
        which displayed in popups and filters on the programs and schedules page. This form will affect
        only the filters which are related to the scheduling, classes, and sessions. By default, all branches
        and camps displayed. If you want to limit the list, you should choose only those you want to show.'),
    ];

    $form['locations'] = [
      '#type' => 'checkboxes',
      '#prefix' => '<div class="fieldgroup form-item form-wrapper"><h2 class="fieldset-legend">' . $this->t('Select locations available for Location filters') . '</h2><div class="fieldset-wrapper">',
      '#suffix' => '</div></div>',
      '#default_value' => array_keys($selected_locations),
      '#options' => ['All' => $this->t('All')] + $locations,
      '#all' => ['All' => $this->t('All')],
      '#branches' => $branches_list['branch'],
      '#camps' => $branches_list['camp'],
      '#description' => $this->t('All locations are not selected = All locations are selected.'),
    ];

    $form['#attached']['library'][] = 'openy_loc_filter/openy_location_filter';

    return parent::buildForm($form, $form_state);
  }

  /**
   * Get all Branch and Camp node id's.
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
    if (isset($locations['All'])) {
      $branches_list = $this->getBranchesList();
      $locations = $branches_list['branch'] + $branches_list['camp'];
    }
    $config->set('locations', $locations)->save();
    Cache::invalidateTags(['rendered']);

    parent::submitForm($form, $form_state);
  }

}

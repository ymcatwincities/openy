<?php

namespace Drupal\openy_group_schedules\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openy_group_schedules\GroupexScheduleFetcher;
use Drupal\openy_group_schedules\DataStorageInterface;
use Drupal\openy_group_schedules\GroupexRequestTrait;

/**
 * Provides a 'Group Schedules' block.
 *
 * @Block(
 *   id = "group_schedules",
 *   admin_label = @Translation("Group Schedules Block"),
 *   category = @Translation("Forms")
 * )
 */
class GroupSchedulesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use GroupexRequestTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $branches;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new Programs Search Block instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   Locations storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory.
   *
   * @internal param $DataStorageInterface
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    $this->branches = $this->getOptions($this->request(['query' => ['locations' => TRUE]]), 'id', 'name');
    $this->configFactory = $configFactory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = $this->configFactory
      ->get('openy_group_schedules.settings')
      ->get('default_locations');

    return [
      'enabled_locations' => $defaults,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $conf = $this->getConfiguration();
    $form = \Drupal::formBuilder()->getForm('Drupal\openy_group_schedules\Form\GroupexFormFull', $conf['enabled_locations']);
    return [
      'form' => $form,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $conf = $this->getConfiguration();
    $form['locations'] = [
      '#type' => 'details',
      '#title' => $this->t('Locations'),
    ];
    $form['locations']['enabled_locations'] = [
      '#type' => 'radios',
      '#title' => $this->t('Enabled Locations'),
      '#options' => ['all' => t('-All-')] + $this->branches,
      '#default_value' => $conf['enabled_locations'] ?: [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $locations = $form_state->getValue('locations', NULL);
    if (is_null($locations)) {
      $conf = NestedArray::getValue($form_state->getValues(), $form['#parents']);
      $locations = $conf['locations'];
    }
    $this->configuration['enabled_locations'] = $locations['enabled_locations'];
  }

  /**
   * Get form item options.
   *
   * @param array|null $data
   *   Data to iterate, or NULL.
   * @param string $key
   *   Key name.
   * @param string $value
   *   Value name.
   *
   * @return array
   *   Array of options.
   */
  protected function getOptions($data, $key, $value) {
    return GroupexScheduleFetcher::getOptions($data, $key, $value);
  }

}

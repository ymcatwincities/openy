<?php

namespace Drupal\openy_programs_search\Plugin\Block;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\openy_programs_search\DataStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Programs Search' block.
 *
 * @Block(
 *   id = "programs_search_block",
 *   admin_label = @Translation("Programs Search Block"),
 *   category = @Translation("Forms")
 * )
 */
class ProgramsSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Locations storage.
   *
   * @var \Drupal\openy_programs_search\DataStorageInterface
   */
  protected $storage;

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
   *   The plugin implementation definition.
   * @param \Drupal\openy_programs_search\DataStorageInterface $storage
   *   Locations storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory.
   *
   * @internal param $DataStorageInterface
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DataStorageInterface $storage, ConfigFactoryInterface $configFactory) {
    $this->storage = $storage;
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
      $container->get('openy_programs_search.data_storage'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $conf = $this->getConfiguration();
    // @todo Use `create()` method to get form builder.
    $form = \Drupal::formBuilder()->getForm('Drupal\openy_programs_search\Form\ProgramsSearchBlockForm', $conf);
    return [
      'form' => $form,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Use system wide default enabled locations.
    $default_locations = $this->configFactory
      ->get('openy_programs_search.settings')
      ->get('default_locations');

    $form = parent::blockForm($form, $form_state);
    $conf = $this->getConfiguration();

    $form['locations_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Locations Config'),
    ];

    $form['locations_config']['enabled_locations'] = [
      '#type' => 'checkboxes',
      '#options' => $this->storage->getLocations(),
      '#default_value' => $conf['enabled_locations'] ?: $default_locations,
    ];

    $form['categories_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Categories Config'),
    ];

    $form['categories_config']['enabled_categories'] = [
      '#type' => 'checkboxes',
      '#options' => $this->storage->getCategories(),
      '#default_value' => $conf['enabled_categories'] ?: [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['enabled_locations'] = array_filter($values['locations_config']['enabled_locations']);
    $this->configuration['enabled_categories'] = array_filter($values['categories_config']['enabled_categories']);
  }

}

<?php

namespace Drupal\ygh_programs_search\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\ygh_programs_search\DataStorageInterface;
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
   * @var \Drupal\ygh_programs_search\DataStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new Programs Search Block instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ygh_programs_search\DataStorageInterface $storage
   *   Locations storage.
   *
   * @internal param $DataStorageInterface
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DataStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ygh_programs_search.data_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'enabled_locations' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $conf = $this->getConfiguration();
    $form = \Drupal::formBuilder()->getForm('Drupal\ygh_programs_search\Form\ProgramsSearchBlockForm', $conf['enabled_locations']);
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
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Locations'),
      '#options' => $this->storage->getLocations(),
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
    $this->configuration['enabled_locations'] = array_filter($locations['enabled_locations']);
  }

}

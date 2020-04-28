<?php

namespace Drupal\openy_programs_search\Plugin\Block;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\openy_programs_search\DataStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;

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
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *
   * @internal param $DataStorageInterface
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DataStorageInterface $storage, ConfigFactoryInterface $configFactory, LoggerInterface $logger, MessengerInterface $messenger) {
    $this->storage = $storage;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
    $this->messenger = $messenger;
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
      $container->get('config.factory'),
      $container->get('logger.factory')->get('openy_programs_search'),
      $container->get('messenger')
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
    $url = Link::fromTextAndUrl($this->t('here'),
      Url::fromRoute('openy_programs_search.settings'))->toString();

    $form['locations_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Locations Config'),
    ];

    try {
      $locations = $this->storage->getLocations();
    }
    catch (\Exception $e) {
      $locations = [];
      $this->logger->error($e->getMessage());
      $this->messenger->addError($this->t('Сan\'t fetch Daxco data, please re-check your settings @url', ['@url' => $url]));
    }

    $form['locations_config']['enabled_locations'] = [
      '#type' => 'checkboxes',
      '#options' => $locations,
      '#default_value' => $conf['enabled_locations'] ?: $default_locations,
    ];

    $form['categories_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Categories Config'),
    ];

    try {
      $categories = $this->storage->getCategories();
    }
    catch (\Exception $e) {
      $categories = [];
      $this->logger->error($e->getMessage());
      $this->messenger->addError($this->t('Сan\'t fetch Daxco data, please re-check your settings @url', ['@url' => $url]));
    }

    $form['categories_config']['enabled_categories'] = [
      '#type' => 'checkboxes',
      '#options' => $categories,
      '#default_value' => $conf['enabled_categories'] ?: [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Escape if not the correct form.
    if (empty($form['#type']) || $form['#type'] !== 'container' || $form['provider']['#value'] !== 'openy_programs_search') {
      return;
    }

    $locations_config = $form_state->getValue('locations_config');
    $categories_config = $form_state->getValue('categories_config');
    if (is_null($locations_config) && is_null($categories_config)) {
      $conf = NestedArray::getValue($form_state->getValues(), $form['#parents']);
      $locations_config = !empty($conf['locations_config']) ? $conf['locations_config'] : ['enabled_locations' => NULL];
      $categories_config = !empty($conf['categories_config']) ? $conf['categories_config'] : ['enabled_categories' => NULL];
    }
    $enabled_locations = is_null($locations_config['enabled_locations']) ? NULL : array_flip($locations_config['enabled_locations']);
    $enabled_categories = is_null($categories_config['enabled_categories']) ? NULL : array_flip($categories_config['enabled_categories']);
    unset($enabled_locations[0]);
    unset($enabled_categories[0]);
    $this->configuration['enabled_locations'] = $enabled_locations;
    $this->configuration['enabled_categories'] = $enabled_categories;
  }

}

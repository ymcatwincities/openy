<?php

namespace Drupal\address\Plugin\Field\FieldWidget;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\InitialValuesEvent;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'address_default' widget.
 *
 * @FieldWidget(
 *   id = "address_default",
 *   label = @Translation("Address"),
 *   field_types = {
 *     "address"
 *   },
 * )
 */
class AddressDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a AddressDefaultWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, CountryRepositoryInterface $country_repository, EventDispatcherInterface $event_dispatcher, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->countryRepository = $country_repository;
    $this->eventDispatcher = $event_dispatcher;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\WidgetPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('address.country_repository'),
      $container->get('event_dispatcher'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_country' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $country_list = $this->countryRepository->getList();
    $element = [];
    $element['default_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Default country'),
      '#options' => ['site_default' => $this->t('- Site default -')] + $country_list,
      '#default_value' => $this->getSetting('default_country'),
      '#empty_value' => '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $default_country = $this->getSetting('default_country');
    if (empty($default_country)) {
      $default_country = $this->t('None');
    }
    elseif ($default_country == 'site_default') {
      $default_country = $this->t('Site default');
    }
    else {
      $country_list = $this->countryRepository->getList();
      $default_country = $country_list[$default_country];
    }
    $summary = [];
    $summary['default_country'] = $this->t('Default country: @country', ['@country' => $default_country]);

    return $summary;
  }

  /**
   * Gets the initial values for the widget.
   *
   * This is a replacement for the disabled default values functionality.
   *
   * @see address_form_field_config_edit_form_alter()
   *
   * @return array
   *   The initial values, keyed by property.
   */
  protected function getInitialValues() {
    $default_country = $this->getSetting('default_country');
    // Resolve the special site_default option.
    if ($default_country == 'site_default') {
      $default_country = $this->configFactory->get('system.date')->get('country.default');
    }

    $initial_values = [
      'country_code' => $default_country,
      'administrative_area' => '',
      'locality' => '',
      'dependent_locality' => '',
      'postal_code' => '',
      'sorting_code' => '',
      'address_line1' => '',
      'address_line2' => '',
      'organization' => '',
      'given_name' => '',
      'additional_name' => '',
      'family_name' => '',
    ];
    // Allow other modules to alter the values.
    $event = new InitialValuesEvent($initial_values, $this->fieldDefinition);
    $this->eventDispatcher->dispatch(AddressEvents::INITIAL_VALUES, $event);
    $initial_values = $event->getInitialValues();

    return $initial_values;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $value = $item->getEntity()->isNew() ? $this->getInitialValues() : $item->toArray();
    // Calling initializeLangcode() every time, and not just when the field
    // is empty, ensures that the langcode can be changed on subsequent
    // edits (because the entity or interface language changed, for example).
    $value['langcode'] = $item->initializeLangcode();

    $element += [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];
    $element['address'] = [
      '#type' => 'address',
      '#default_value' => $value,
      '#required' => $this->fieldDefinition->isRequired(),
      '#available_countries' => $item->getAvailableCountries(),
      '#field_overrides' => $item->getFieldOverrides(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    $error_element = NestedArray::getValue($element['address'], $violation->arrayPropertyPath);
    return is_array($error_element) ? $error_element : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values as $delta => $value) {
      $new_values[$delta] = $value['address'];
    }
    return $new_values;
  }

}

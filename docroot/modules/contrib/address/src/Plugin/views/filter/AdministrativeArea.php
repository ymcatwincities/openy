<?php

namespace Drupal\address\Plugin\views\filter;

use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\address\LabelHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by administrative area.
 *
 * @todo: Rebuild the exposed filter element via AJAX when the country changes.
 * @see https://www.drupal.org/node/2840717
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("administrative_area")
 */
class AdministrativeArea extends CountryAwareInOperatorBase {

  /**
   * The address format repository.
   *
   * @var \CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface
   */
  protected $addressFormatRepository;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * If we're in the middle of building a form, its current state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * The currently selected country (if any).
   *
   * @var string
   */
  protected $currentCountryCode;

  /**
   * Constructs a new AdministrativeArea object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface $address_format_repository
   *   The address format repository.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface $subdivision_repository
   *   The subdivision repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CountryRepositoryInterface $country_repository, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, AddressFormatRepositoryInterface $address_format_repository, SubdivisionRepositoryInterface $subdivision_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $country_repository, $entity_type_manager, $entity_field_manager);

    $this->addressFormatRepository = $address_format_repository;
    $this->subdivisionRepository = $subdivision_repository;
    $this->formState = NULL;
    $this->currentCountryCode = '';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('address.country_repository'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('address.address_format_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['country'] = [
      'contains' => [
        'country_source' => ['default' => ''],
        'country_argument_id' => ['default' => ''],
        'country_filter_id' => ['default' => ''],
        'country_static_code' => ['default' => ''],
      ],
    ];
    $options['expose']['contains']['label_type']['default'] = 'static';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function canBuildGroup() {
    // To be able to define a group, you have to be able to select values
    // while configuring the filter. But this filter doesn't let you select
    // values until a country is selected, so the group filter functionality
    // is impossible.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $this->formState = $form_state;

    $form['country'] = [
      '#type' => 'container',
      '#weight' => -300,
    ];
    $form['country']['country_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Country source'),
      '#options' => [
        'static' => $this->t('A predefined country code'),
        'argument' => $this->t('The value of a contextual filter'),
        'filter' => $this->t('The value of an exposed filter'),
      ],
      '#default_value' => $this->options['country']['country_source'],
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefreshCountry'],
        'wrapper' => 'admin-area-value-options-ajax-wrapper',
      ],
    ];

    $argument_options = [];
    // Find all the contextual filters on the display to use as options.
    foreach ($this->view->display_handler->getHandlers('argument') as $name => $argument) {
      // @todo Limit this to arguments pointing to a country code field.
      $argument_options[$name] = $argument->adminLabel();
    }
    if (!empty($argument_options)) {
      $form['country']['country_argument_id'] = [
        '#type' => 'select',
        '#title' => t('Country contextual filter'),
        '#options' => $argument_options,
        '#default_value' => $this->options['country']['country_argument_id'],
      ];
    }
    else {
      // #states doesn't work on markup elements, so use a container.
      $form['country']['country_argument_id'] = [
        '#type' => 'container',
      ];
      $form['country']['country_argument_id']['error'] = [
        '#type' => 'markup',
        '#markup' => t('You must add a contextual filter for the country code to use this filter for administrative areas.'),
      ];
    }
    $form['country']['country_argument_id']['#states'] = [
      'visible' => [
        ':input[name="options[country][country_source]"]' => ['value' => 'argument'],
      ],
    ];

    $filter_options = [];
    // Find all country_code filters from address.module for the valid choices.
    foreach ($this->view->display_handler->getHandlers('filter') as $name => $filter) {
      $definition = $filter->pluginDefinition;
      if ($definition['id'] == 'country_code' && $definition['provider'] == 'address') {
        $filter_options[$name] = $filter->adminLabel();
      }
    }
    if (!empty($filter_options)) {
      $form['country']['country_filter_id'] = [
        '#type' => 'select',
        '#title' => t('Exposed country filter to determine values'),
        '#options' => $filter_options,
        '#default_value' => $this->options['country']['country_filter_id'],
      ];
    }
    else {
      // #states doesn't work on markup elements, so we to use a container.
      $form['country']['country_filter_id'] = [
        '#type' => 'container',
      ];
      $form['country']['country_filter_id']['error'] = [
        '#type' => 'markup',
        '#markup' => t('You must add a filter for the country code to use this filter for administrative areas.'),
      ];
    }
    $form['country']['country_filter_id']['#states'] = [
      'visible' => [
        ':input[name="options[country][country_source]"]' => ['value' => 'filter'],
      ],
    ];

    $countries = $this->getAdministrativeAreaCountries();

    $form['country']['country_static_code'] = [
      '#type' => 'select',
      '#title' => t('Predefined country for administrative areas'),
      '#options' => $countries,
      '#empty_value' => '',
      '#default_value' => $this->options['country']['country_static_code'],
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefreshCountry'],
        'wrapper' => 'admin-area-value-options-ajax-wrapper',
      ],
      '#states' => [
        'visible' => [
          ':input[name="options[country][country_source]"]' => ['value' => 'static'],
        ],
      ],
    ];

    // @todo This should appear directly above $form['expose']['label'].
    $form['expose']['label_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Label type'),
      '#options' => [
        'static' => $this->t('Static'),
        'dynamic' => $this->t('Dynamic (an appropriate label will be set based on the active country)'),
      ],
      '#default_value' => $this->options['expose']['label_type'],
      '#states' => [
        'visible' => [
          ':input[name="options[expose_button][checkbox][checkbox]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if (empty($form_state)) {
      return;
    }
    $is_exposed = !empty($this->options['exposed']);

    $country_source = $form_state->getValue(['options', 'country', 'country_source']);
    switch ($country_source) {
      case 'argument':
        $country_argument = $form_state->getValue(['options', 'country', 'country_argument_id']);
        if (empty($country_argument)) {
          $error = $this->t("The country contextual filter must be defined for this filter to work using 'contextual filter' for the 'Country source'.");
          $form_state->setError($form['country']['country_source'], $error);
        }
        if (empty($is_exposed)) {
          $error = $this->t('This filter must be exposed to use a contextual filter to specify the country.');
          $form_state->setError($form['country']['country_source'], $error);
        }
        break;

      case 'filter':
        $country_filter = $form_state->getValue(['options', 'country', 'country_filter_id']);
        if (empty($country_filter)) {
          $error = $this->t("The country filter must be defined for this filter to work using 'exposed filter' for the 'Country source'.");
          $form_state->setError($form['country']['country_source'], $error);
        }
        if (empty($is_exposed)) {
          $error = $this->t('This filter must be exposed to use a filter to specify the country.');
          $form_state->setError($form['country']['country_source'], $error);
        }
        break;

      case 'static':
        $country_code = $form_state->getValue(['options', 'country', 'country_static_code']);
        if (empty($country_code)) {
          $error = $this->t('The predefined country must be set for this filter to work.');
          $form_state->setError($form['country']['country_static_code'], $error);
        }
        break;

      default:
        $error = $this->t('The  source for the country must be defined for this filter to work.');
        $form_state->setError($form['country']['country_source'], $error);

        break;
    }

    parent::validateOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    // Only show the label element if we're configured for a static label.
    $form['expose']['label']['#states'] = [
      'visible' => [
        ':input[name="options[expose][label_type]"]' => ['value' => 'static'],
      ],
    ];
    // Only show the reduce option if we have a static country. If we're
    // getting values from a filter or argument, there are no fixed values to
    // reduce to.
    $form['expose']['reduce']['#states'] = [
      'visible' => [
        ':input[name="options[country][country_source]"]' => ['value' => 'static'],
      ],
    ];
    // Repair the wrapper container on $form['value'] clobbered by
    // FilterPluginBase::buildExposeForm().
    $form['value']['#prefix'] = '<div id="admin-area-value-options-ajax-wrapper" class="views-group-box views-right-60">';
    $form['value']['#suffix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitExposeForm($form, FormStateInterface $form_state) {
    // If the country source is anything other than static, we have to
    // ignore/disable the "reduce" option since it doesn't make any sense and
    // will cause problems if the stale configuration is saved.
    // Similarly, we clear out any selections for specific administrative areas.
    $country_source = $form_state->getValue(['options', 'country', 'country_source']);
    if ($country_source != 'static') {
      $form_state->setValue(['options', 'expose', 'reduce'], FALSE);
      $form_state->setValue(['options', 'value'], []);
    }
    parent::submitExposeForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function showValueForm(&$form, FormStateInterface $form_state) {
    $this->valueForm($form, $form_state);
    $form['value']['#prefix'] = '<div id="admin-area-value-options-ajax-wrapper">';
    $form['value']['#suffix'] = '</div>';
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    $this->valueOptions = [];
    $this->formState = $form_state;

    $country_source = $this->getCountrySource();

    if ($country_source == 'static' || $form_state->get('exposed')) {
      $this->getCurrentCountry();
      parent::valueForm($form, $form_state);
      $form['value']['#after_build'][] = [get_class($this), 'clearValues'];
    }
    else {
      $form['value'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'admin-area-value-options-ajax-wrapper'],
      ];
      $form['value']['message'] = [
        '#type' => 'markup',
        '#markup' => t("You can only select options here if you use a predefined country for the 'Country source'."),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    $this->formState = $form_state;
    $country_source = $this->getCountrySource();
    if ($country_source == 'static') {
      // Only save the values if we've got a static country code.
      parent::valueSubmit($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exposedInfo() {
    $info = parent::exposedInfo();
    if ($this->options['expose']['label_type'] == 'dynamic') {
      $current_country = $this->getCurrentCountry();
      if (!empty($current_country)) {
        $address_format = $this->addressFormatRepository->get($current_country);
        $labels = LabelHelper::getFieldLabels($address_format);
        if (!empty($labels['administrativeArea'])) {
          $info['label'] = $labels['administrativeArea'];
        }
      }
    }
    return $info;
  }

  /**
   * Gets the current source for the country code.
   *
   * If defined in the current values of the configuration form, use
   * that. Otherwise, fall back to the filter configuration.
   *
   * @return string
   *   The country source.
   */
  protected function getCountrySource() {
    // If we're rebuilding via AJAX, we want the country source from the form
    // state, not the configuration.
    $country_source = '';
    if (!empty($this->formState)) {
      // First, see if there's a legitimate value in the form state.
      $form_value_country_source = $this->formState->getValue(['options', 'country', 'country_source']);
      if (!empty($form_value_country_source)) {
        $country_source = $form_value_country_source;
      }
      else {
        // At various stages of building/validating the form, we might have
        // user input but not yet have the value saved into the form
        // state. So, if we have a form state but still don't have a value,
        // see if it is defined in the user input.
        $input = $this->formState->getUserInput();
        if (!empty($input['options']['country']['country_source'])) {
          $country_source = $input['options']['country']['country_source'];
        }
      }
    }
    // If we don't have a source via the form state, use our configuration.
    if (empty($country_source)) {
      $country_source = $this->options['country']['country_source'];
    }
    return $country_source;
  }

  /**
   * Gets the currently active country code.
   *
   * The country source determines where to look for the country code. It can
   * either be predefined, in which case we simply return the current value of
   * the static country code (via form values or configuration). We can look
   * for the country via a Views argument, in which case we determine the
   * current value of the argument. Or we can get the country from another
   * exposed filter, in which case we look in the form values to find the
   * current country code from the other filter.
   *
   * @return string
   *   The 2-letter country code.
   */
  protected function getCurrentCountry() {
    $this->currentCountryCode = '';
    switch ($this->getCountrySource()) {
      case 'argument':
        $country_argument = $this->view->display_handler->getHandler('argument', $this->options['country']['country_argument_id']);
        if (!empty($country_argument)) {
          $this->currentCountryCode = $country_argument->getValue();
        }
        break;

      case 'filter':
        $country_filter = $this->view->display_handler->getHandler('filter', $this->options['country']['country_filter_id']);
        if (!empty($country_filter) && !empty($this->formState)) {
          $input = $this->formState->getUserInput();
          $country_filter_identifier = $country_filter->options['expose']['identifier'];
          if (!empty($input[$country_filter_identifier])) {
            if (is_array($input[$country_filter_identifier])) {
              // @todo Maybe the config validation should prevent multi-valued
              // country filters. For now, we only provide administrative area
              // options if a single country is selected.
              if (count($input[$country_filter_identifier]) == 1) {
                $this->currentCountryCode = array_shift($input[$country_filter_identifier]);
              }
            }
            else {
              $this->currentCountryCode = $input[$country_filter_identifier];
            }
          }
        }
        break;

      case 'static':
        if (!empty($this->formState)) {
          // During filter configuration validation, we still need to know the
          // current country code, but the values won't yet be saved into the
          // ones accessible via FormStateInterface::getValue(). So, directly
          // inspect the user input instead of the official form values.
          $input = $this->formState->getUserInput();
          if (!empty($input['options']['country']['country_static_code'])) {
            $form_input_country_code = $input['options']['country']['country_static_code'];
          }
        }
        $this->currentCountryCode = !empty($form_input_country_code) ? $form_input_country_code : $this->options['country']['country_static_code'];
        break;

    }

    // Since the country code can come from all sorts of non-validated user
    // input (e.g. GET parameters) and since it might be 'All', ensure we've
    // got a valid country code before we proceed. Other code in this
    // filter (and especially upstream in the AddressFormatRepository and
    // others) will explode if passed an invalid country code.
    if (!empty($this->currentCountryCode)) {
      $all_countries = $this->countryRepository->getList();
      if (empty($all_countries[$this->currentCountryCode])) {
        $this->currentCountryCode = '';
      }
    }
    return $this->currentCountryCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = [];
    if (($country_code = $this->getCurrentCountry())) {
      $parents[] = $country_code;
      $locale = \Drupal::languageManager()->getConfigOverrideLanguage()->getId();
      $subdivisions = $this->subdivisionRepository->getList($parents, $locale);
      $this->valueOptions = $subdivisions;
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    // Hide the form element if we have no options to select.
    // (e.g. the country isn't set or it doesn't use administrative areas).
    if (empty($this->valueOptions)) {
      $identifier = $this->options['expose']['identifier'];
      $form[$identifier]['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    switch ($this->options['country']['country_source']) {
      case 'argument':
        return $this->t('exposed: country set via contextual filter');

      case 'filter':
        return $this->t('exposed: country set via exposed filter');

      case 'static':
        if (!empty($this->options['exposed'])) {
          return $this->t('exposed: fixed country: @country', ['@country' => $this->options['country']['country_static_code']]);
        }
        return $this->t('fixed country: @country', ['@country' => $this->options['country']['country_static_code']]);
    }
    return $this->t('broken configuration');
  }

  /**
   * Gets a list of countries that have administrative areas.
   *
   * @param array $available_countries
   *   The available countries to filter by.
   *   Defaults to the available countries for this filter.
   *
   * @return array
   *   An array of country names, keyed by country code.
   */
  public function getAdministrativeAreaCountries(array $available_countries = NULL) {
    if (!isset($available_countries)) {
      $available_countries = $this->getAvailableCountries();
    }

    $countries = [];
    foreach ($available_countries as $country_code => $country_name) {
      $address_format = $this->addressFormatRepository->get($country_code);
      $subdivision_depth = $address_format->getSubdivisionDepth();
      if ($subdivision_depth > 0) {
        $countries[$country_code] = $country_name;
      }
    }

    return $countries;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefreshCountry(array $form, FormStateInterface $form_state) {
    return $form['options']['value'];
  }

  /**
   * Clears the administrative area form values when the country changes.
   *
   * Implemented as an #after_build callback because #after_build runs before
   * validation, allowing the values to be cleared early enough to prevent the
   * "Illegal choice" error.
   */
  public static function clearValues(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!$triggering_element) {
      return $element;
    }

    $triggering_element_name = end($triggering_element['#parents']);
    if ($triggering_element_name == 'country_static_code' || $triggering_element_name == 'country_source') {
      foreach ($element['#options'] as $key => $option) {
        $element[$key]['#value'] = 0;
      }
      $element['#value'] = [];

      $input = &$form_state->getUserInput();
      $input['options']['value'] = [];
    }

    return $element;
  }

}

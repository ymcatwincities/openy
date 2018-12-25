<?php

namespace Drupal\search_api_solr\Plugin\search_api\backend;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Url;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\search_api\Query\ConditionInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api\Utility\Utility as SearchApiUtility;
use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Suggestion;
use Drupal\search_api_autocomplete\Suggestion\SuggestionFactory;
use Drupal\search_api_solr\SearchApiSolrException;
use Drupal\search_api_solr\SolrBackendInterface;
use Drupal\search_api_solr\SolrConnector\SolrConnectorPluginManager;
use Drupal\search_api_solr\Utility\Utility as SearchApiSolrUtility;
use Solarium\Core\Client\Response;
use Solarium\Core\Query\QueryInterface as SolariumQueryInterface;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\Exception\ExceptionInterface;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;
use Solarium\QueryType\Suggester\Query as SuggesterQuery;
use Solarium\QueryType\Suggester\Result\Result as SuggesterResult;
use Solarium\QueryType\Update\Query\Document\Document;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The minimum required Solr schema version.
 */
define('SEARCH_API_SOLR_MIN_SCHEMA_VERSION', 4);

define('SEARCH_API_ID_FIELD_NAME', 'ss_search_api_id');

/**
 * Apache Solr backend for search api.
 *
 * @SearchApiBackend(
 *   id = "search_api_solr",
 *   label = @Translation("Solr"),
 *   description = @Translation("Index items using an Apache Solr search server.")
 * )
 */
class SearchApiSolrBackend extends BackendPluginBase implements SolrBackendInterface, PluginFormInterface {

  use PluginFormTrait {
    submitConfigurationForm as traitSubmitConfigurationForm;
  }

  /**
   * Metadata describing fields on the Solr/Lucene index.
   *
   * @var string[][]
   */
  protected $fieldNames = array();

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A config object for 'search_api_solr.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $searchApiSolrSettings;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The backend plugin manager.
   *
   * @var \Drupal\search_api_solr\SolrConnector\SolrConnectorPluginManager
   */
  protected $solrConnectorPluginManager;

  /**
   * @var \Drupal\search_api_solr\SolrConnectorInterface
   */
  protected $solrConnector;

  /**
   * @var \Drupal\search_api\Utility\FieldsHelperInterface
   */
  protected $fieldsHelper;

  /**
   * The data type helper.
   *
   * @var \Drupal\search_api\Utility\DataTypeHelper|null
   */
  protected $dataTypeHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, Config $search_api_solr_settings, LanguageManagerInterface $language_manager, SolrConnectorPluginManager $solr_connector_plugin_manager, FieldsHelperInterface $fields_helper, DataTypeHelperInterface $dataTypeHelper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
    $this->searchApiSolrSettings = $search_api_solr_settings;
    $this->languageManager = $language_manager;
    $this->solrConnectorPluginManager = $solr_connector_plugin_manager;
    $this->fieldsHelper = $fields_helper;
    $this->dataTypeHelper = $dataTypeHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('config.factory')->get('search_api_solr.settings'),
      $container->get('language_manager'),
      $container->get('plugin.manager.search_api_solr.connector'),
      $container->get('search_api.fields_helper'),
      $container->get('search_api.data_type_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'excerpt' => FALSE,
      'retrieve_data' => FALSE,
      'highlight_data' => FALSE,
      'skip_schema_check' => FALSE,
      'site_hash' => FALSE,
      'suggest_suffix' => TRUE,
      'suggest_corrections' => TRUE,
      'suggest_words' => FALSE,
      // Set the default for new servers to NULL to force "safe" un-selected
      // radios. @see https://www.drupal.org/node/2820244
      'connector' => NULL,
      'connector_config' => [],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (!$this->server->isNew()) {
      // Editing this server.
      $form['server_description'] = array(
        '#type' => 'item',
        '#title' => $this->t('Solr server URI'),
        '#description' => $this->getSolrConnector()->getServerLink(),
      );
    }

    $solr_connector_options = $this->getSolrConnectorOptions();
    $form['connector'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Solr Connector'),
      '#description' => $this->t('Choose a connector to use for this Solr server.'),
      '#options' => $solr_connector_options,
      '#default_value' => $this->configuration['connector'],
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => [get_class($this), 'buildAjaxSolrConnectorConfigForm'],
        'wrapper' => 'search-api-solr-connector-config-form',
        'method' => 'replace',
        'effect' => 'fade',
      ),
    );

    $this->buildConnectorConfigForm($form, $form_state);

    $form['advanced'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['advanced']['retrieve_data'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Retrieve result data from Solr'),
      '#description' => $this->t('When checked, result data will be retrieved directly from the Solr server. This might make item loads unnecessary. Only indexed fields can be retrieved. Note also that the returned field data might not always be correct, due to preprocessing and caching issues.'),
      '#default_value' => $this->configuration['retrieve_data'],
    );
    $form['advanced']['highlight_data'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Highlight retrieved data'),
      '#description' => $this->t('When retrieving result data from the Solr server, try to highlight the search terms in the returned fulltext fields.'),
      '#default_value' => $this->configuration['highlight_data'],
    );
    $form['advanced']['excerpt'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Return an excerpt for all results'),
      '#description' => $this->t("If search keywords are given, use Solr's capabilities to create a highlighted search excerpt for each result. Whether the excerpts will actually be displayed depends on the settings of the search, though."),
      '#default_value' => $this->configuration['excerpt'],
    );
    $form['advanced']['skip_schema_check'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Skip schema verification'),
      '#description' => $this->t('Skip the automatic check for schema-compatibillity. Use this override if you are seeing an error-message about an incompatible schema.xml configuration file, and you are sure the configuration is compatible.'),
      '#default_value' => $this->configuration['skip_schema_check'],
    );
    // Highlighting retrieved data only makes sense when we retrieve data.
    // (Actually, internally it doesn't really matter. However, from a user's
    // perspective, having to check both probably makes sense.)
    $form['advanced']['highlight_data']['#states']['invisible'][':input[name="backend_config[advanced][retrieve_data]"]']['checked'] = FALSE;

    if ($this->moduleHandler->moduleExists('search_api_autocomplete')) {
      $form['autocomplete'] = array(
        '#type' => 'details',
        '#title' => $this->t('Autocomplete settings'),
        '#description' => $this->t('These settings allow you to configure how suggestions are computed when autocompletion is used. If you are seeing many inappropriate suggestions you might want to deactivate the corresponding suggestion type. You can also deactivate one method to speed up the generation of suggestions.'),
      );
      $form['autocomplete']['suggest_suffix'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Suggest word endings'),
        '#description' => $this->t('Suggest endings for the currently entered word.'),
        '#default_value' => $this->configuration['suggest_suffix'],
      );
      $form['autocomplete']['suggest_corrections'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Suggest corrected words'),
        '#description' => $this->t('Suggest corrections for the currently entered words.'),
        '#default_value' => $this->configuration['suggest_corrections'],
      );
      $form['autocomplete']['suggest_words'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Suggest additional words'),
        '#description' => $this->t('Suggest additional words the user might want to search for.'),
        '#default_value' => $this->configuration['suggest_words'],
        // @todo
        '#disabled' => TRUE,
      );
    }

    $form['multisite'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Multi-site compatibility'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => $this->t("By default a single Solr backend based Search API server is able to index the data of multiple Drupal sites. But this is an expert-only and dangerous feature that mainly exists for backward compatibility. If you really index multiple sites in one index and don't activate 'Retrieve results for this site only' below you have to ensure that you enable 'Retrieve result data from Solr'! Otherwise it could lead to any kind of errors!"),
    );
    $description = $this->t("Automatically filter all searches to only retrieve results from this Drupal site. The default and intended behavior is to display results from all sites. WARNING: Enabling this filter might break features like autocomplete, spell checking or suggesters!");
    $form['multisite']['site_hash'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Retrieve results for this site only'),
      '#description' => $description,
      '#default_value' => $this->configuration['site_hash'],
    );

    return $form;
  }

  /**
   * Returns all available backend plugins, as an options list.
   *
   * @return string[]
   *   An associative array mapping backend plugin IDs to their (HTML-escaped)
   *   labels.
   */
  protected function getSolrConnectorOptions() {
    $options = [];
    foreach ($this->solrConnectorPluginManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = Html::escape($plugin_definition['label']);
    }
    return $options;
  }

  /**
   * Builds the backend-specific configuration form.
   *
   * @param \Drupal\search_api_solr\SolrConnectorInterface $connector
   *   The server that is being created or edited.
   */
  public function buildConnectorConfigForm(array &$form, FormStateInterface $form_state) {
    $form['connector_config'] = [];

    $connector_id = $this->configuration['connector'];
    if ($connector_id) {
      $connector = $this->solrConnectorPluginManager->createInstance($connector_id, $this->configuration['connector_config']);
      if ($connector instanceof PluginFormInterface) {
        $form_state->set('connector', $connector_id);
        if ($form_state->isRebuilding()) {
          drupal_set_message($this->t('Please configure the selected Solr connector.'), 'warning');
        }
        // Attach the Solr connector plugin configuration form.
        $connector_form_state = SubformState::createForSubform($form['connector_config'], $form, $form_state);
        $form['connector_config'] = $connector->buildConfigurationForm($form['connector_config'], $connector_form_state);

        // Modify the backend plugin configuration container element.
        $form['connector_config']['#type'] = 'details';
        $form['connector_config']['#title'] = $this->t('Configure %plugin Solr connector', array('%plugin' => $connector->label()));
        $form['connector_config']['#description'] = $connector->getDescription();
        $form['connector_config']['#open'] = TRUE;
      }
    }
    $form['connector_config'] += ['#type' => 'container'];
    $form['connector_config']['#attributes'] = [
      'id' => 'search-api-solr-connector-config-form',
    ];
    $form['connector_config']['#tree'] = TRUE;

  }

  /**
   * Handles switching the selected Solr connector plugin.
   */
  public static function buildAjaxSolrConnectorConfigForm(array $form, FormStateInterface $form_state) {
    // The work is already done in form(), where we rebuild the entity according
    // to the current form values and then create the backend configuration form
    // based on that. So we just need to return the relevant part of the form
    // here.
    return $form['backend_config']['connector_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Check if the Solr connector plugin changed.
    if ($form_state->getValue('connector') != $form_state->get('connector')) {
      $new_connector = $this->solrConnectorPluginManager->createInstance($form_state->getValue('connector'));
      if ($new_connector instanceof PluginFormInterface) {
        $form_state->setRebuild();
      }
      else {
        $form_state->setError($form['connector'], $this->t('The connector could not be activated.'));
      }
    }
    // Check before loading the backend plugin so we don't throw an exception.
    else {
      $this->configuration['connector'] = $form_state->get('connector');
      $connector = $this->getSolrConnector();
      if ($connector instanceof PluginFormInterface) {
        $connector_form_state = SubformState::createForSubform($form['connector_config'], $form, $form_state);
        $connector->validateConfigurationForm($form['connector_config'], $connector_form_state);
      }
      else {
        $form_state->setError($form['connector'], $this->t('The connector could not be activated.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['connector'] = $form_state->get('connector');
    $connector = $this->getSolrConnector();
    if ($connector instanceof PluginFormInterface) {
      $connector_form_state = SubformState::createForSubform($form['connector_config'], $form, $form_state);
      $connector->submitConfigurationForm($form['connector_config'], $connector_form_state);
    }

    $values = $form_state->getValues();
    // Since the form is nested into another, we can't simply use #parents for
    // doing this array restructuring magic. (At least not without creating an
    // unnecessary dependency on internal implementation.)
    $values += $values['advanced'];
    $values += $values['multisite'];
    if (!empty($values['autocomplete'])) {
      $values += $values['autocomplete'];
    }
    else {
      $defaults = $this->defaultConfiguration();
      $values['suggest_suffix'] = $defaults['suggest_suffix'];
      $values['suggest_corrections'] = $defaults['suggest_corrections'];
      $values['suggest_words'] = $defaults['suggest_words'];
    }

    // Highlighting retrieved data only makes sense when we retrieve data from
    // the Solr backend.
    $values['highlight_data'] &= $values['retrieve_data'];

    foreach ($values as $key => $value) {
      $form_state->setValue($key, $value);
    }

    // Clean-up the form to avoid redundant entries in the stored configuration.
    $form_state->unsetValue('advanced');
    $form_state->unsetValue('multisite');
    $form_state->unsetValue('autocomplete');
    // The server description is a #type item element, which means it has a
    // value, do not save it.
    $form_state->unsetValue('server_description');

    $this->traitSubmitConfigurationForm($form, $form_state);

    // Delete cached endpoint data.
    \Drupal::state()->delete('search_api_solr.endpoint.data');
  }

  /**
   * {@inheritdoc}
   */
  public function getSolrConnector() {
    if (!$this->solrConnector) {
      if (!($this->solrConnector = $this->solrConnectorPluginManager->createInstance($this->configuration['connector'], $this->configuration['connector_config']))) {
        throw new SearchApiException("The Solr Connector with ID '$this->configuration['connector']' could not be retrieved.");
      }
    }
    return $this->solrConnector;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    $conn = $this->getSolrConnector();
    return $conn->pingCore() !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedFeatures() {
    return [
      'search_api_autocomplete',
      'search_api_facets',
      'search_api_facets_operator_or',
      'search_api_mlt',
      'search_api_random_sort',
      'search_api_data_type_location',
      // 'search_api_grouping',
      // 'search_api_spellcheck',
      // 'search_api_data_type_geohash',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDataType($type) {
    return in_array($type, [
      'location',
      'rpt',
      'solr_string_ngram',
      'solr_string_storage',
      'solr_text_ngram',
      'solr_text_omit_norms',
      'solr_text_phonetic',
      'solr_text_unstemmed',
      'solr_text_wstoken',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    $connector = $this->getSolrConnector();

    $info[] = [
      'label' => $this->t('Solr connector plugin'),
      'info' => $connector->label(),
    ];

    $info[] = [
      'label' => $this->t('Solr server URI'),
      'info' => $connector->getServerLink(),
    ];

    $info[] = [
      'label' => $this->t('Solr core URI'),
      'info' => $connector->getCoreLink(),
    ];

    // Add connector-specific information.
    $info = array_merge($info, $connector->viewSettings());

    if ($this->server->status()) {
      // If the server is enabled, check whether Solr can be reached.
      $ping_server = $connector->pingServer();
      if ($ping_server) {
        $msg = $this->t('The Solr server could be reached.');
      }
      else {
        $msg = $this->t('The Solr server could not be reached or is protected by your service provider.');
      }
      $info[] = [
        'label' => $this->t('Server Connection'),
        'info' => $msg,
        'status' => $ping_server ? 'ok' : 'error',
      ];

      $ping = $connector->pingCore();
      if ($ping) {
        $msg = $this->t('The Solr core could be accessed (latency: @millisecs ms).', ['@millisecs' => $ping * 1000]);
      }
      else {
        $msg = $this->t('The Solr core could not be accessed. Further data is therefore unavailable.');
      }
      $info[] = [
        'label' => $this->t('Core Connection'),
        'info' => $msg,
        'status' => $ping ? 'ok' : 'error',
      ];

      $version = $connector->getSolrVersion();
      $info[] = [
        'label' => $this->t('Configured Solr Version'),
        'info' => $version,
        'status' => version_compare($version, '0.0.0', '>') ? 'ok' : 'error',
      ];

      if ($ping_server || $ping) {
        $info[] = [
          'label' => $this->t('Detected Solr Version'),
          'info' => $connector->getSolrVersion(TRUE),
          'status' => 'ok',
        ];

        try {
          // If Solr can be reached, provide more information. This isn't done
          // often (only when an admin views the server details), so we clear
          // the cache to get the current data.
          $data = $connector->getLuke();
          if (isset($data['index']['numDocs'])) {
            // Collect the stats.
            $stats_summary = $connector->getStatsSummary();

            $pending_msg = $stats_summary['@pending_docs'] ? $this->t('(@pending_docs sent but not yet processed)', $stats_summary) : '';
            $index_msg = $stats_summary['@index_size'] ? $this->t('(@index_size on disk)', $stats_summary) : '';
            $indexed_message = $this->t('@num items @pending @index_msg', array(
              '@num' => $data['index']['numDocs'],
              '@pending' => $pending_msg,
              '@index_msg' => $index_msg,
            ));
            $info[] = [
              'label' => $this->t('Indexed'),
              'info' => $indexed_message,
            ];

            if (!empty($stats_summary['@deletes_total'])) {
              $info[] = [
                'label' => $this->t('Pending Deletions'),
                'info' => $stats_summary['@deletes_total'],
              ];
            }

            $info[] = [
              'label' => $this->t('Delay'),
              'info' => $this->t('@autocommit_time before updates are processed.', $stats_summary),
            ];

            $status = 'ok';
            if (empty($this->configuration['skip_schema_check'])) {
              if (substr($stats_summary['@schema_version'], 0, 10) == 'search-api') {
                drupal_set_message($this->t('Your schema.xml version is too old. Please replace all configuration files with the ones packaged with this module and re-index you data.'), 'error');
                $status = 'error';
              }
              elseif (!preg_match('/drupal-[' . SEARCH_API_SOLR_MIN_SCHEMA_VERSION . '-9]\./', $stats_summary['@schema_version'])) {
                $variables['@url'] = Url::fromUri('internal:/' . drupal_get_path('module', 'search_api_solr') . '/INSTALL.txt')
                  ->toString();
                $message = $this->t('You are using an incompatible schema.xml configuration file. Please follow the instructions in the <a href="@url">INSTALL.txt</a> file for setting up Solr.', $variables);
                drupal_set_message($message, 'error');
                $status = 'error';
              }
            }
            $info[] = [
              'label' => $this->t('Schema'),
              'info' => $stats_summary['@schema_version'],
              'status' => $status,
            ];

            if (!empty($stats_summary['@core_name'])) {
              $info[] = [
                'label' => $this->t('Solr Core Name'),
                'info' => $stats_summary['@core_name'],
              ];
            }
          }
        }
        catch (SearchApiException $e) {
          $info[] = [
            'label' => $this->t('Additional information'),
            'info' => $this->t('An error occurred while trying to retrieve additional information from the Solr server: %msg', ['%msg' => $e->getMessage()]),
            'status' => 'error',
          ];
        }
      }
    }

    if ($this->moduleHandler->moduleExists('search_api_autocomplete')) {
      $autocomplete_modes = [];
      if ($this->configuration['suggest_suffix']) {
        $autocomplete_modes[] = $this->t('Suggest word endings');
      }
      if ($this->configuration['suggest_corrections']) {
        $autocomplete_modes[] = $this->t('Suggest corrected words');
      }
      if ($this->configuration['suggest_words']) {
        $autocomplete_modes[] = $this->t('Suggest additional words');
      }

      $info[] = [
        'label' => $this->t('Autocomplete suggestions'),
        'info' => !empty($autocomplete_modes) ? implode('; ', $autocomplete_modes) : $this->t('none'),
      ];
    }

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex(IndexInterface $index) {
    if ($this->indexFieldsUpdated($index)) {
      $index->reindex();
    }
  }

  /**
   * Checks if the recently updated index had any fields changed.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index that was just updated.
   *
   * @return bool
   *   TRUE if any of the fields were updated, FALSE otherwise.
   */
  protected function indexFieldsUpdated(IndexInterface $index) {
    // Get the original index, before the update. If it cannot be found, err on
    // the side of caution.
    if (!isset($index->original)) {
      return TRUE;
    }
    /** @var \Drupal\search_api\IndexInterface $original */
    $original = $index->original;

    $old_fields = $original->getFields();
    $new_fields = $index->getFields();
    if (!$old_fields && !$new_fields) {
      return FALSE;
    }
    if (array_diff_key($old_fields, $new_fields) || array_diff_key($new_fields, $old_fields)) {
      return TRUE;
    }
    $old_field_names = $this->getSolrFieldNames($original, TRUE);
    $new_field_names = $this->getSolrFieldNames($index, TRUE);
    return $old_field_names != $new_field_names;
  }

  /**
   * {@inheritdoc}
   */
  public function removeIndex($index) {
    // Only delete the index's data if the index isn't read-only. If the index
    // has already been deleted and we only get the ID, we just assume it was
    // read-only to be on the safe side.
    if (is_object($index) && !$index->isReadOnly()) {
      $this->deleteAllIndexItems($index);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    $connector = $this->getSolrConnector();
    $update_query = $connector->getUpdateQuery();
    $documents = $this->getDocuments($index, $items, $update_query);
    if (!$documents) {
      return [];
    }
    try {
      $update_query->addDocuments($documents);
      $connector->update($update_query);

      $ret = [];
      foreach ($documents as $document) {
        $ret[] = $document->getFields()[SEARCH_API_ID_FIELD_NAME];
      }
      return $ret;
    }
    catch (\Exception $e) {
      watchdog_exception('search_api_solr', $e, "%type while indexing: @message in %function (line %line of %file).");
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDocument(IndexInterface $index, ItemInterface $item) {
    $documents = $this->getDocuments($index, [$item->getId() => $item]);
    return reset($documents);
  }

  /**
   * {@inheritdoc}
   */
  public function getDocuments(IndexInterface $index, array $items, UpdateQuery $update_query = NULL) {
    $connector = $this->getSolrConnector();
    $schema_version = $connector->getSchemaVersion();

    $documents = array();
    $index_id = $this->getIndexId($index->id());
    $field_names = $this->getSolrFieldNames($index);
    $languages = $this->languageManager->getLanguages();
    $base_urls = array();

    if (!$update_query) {
      $update_query = $connector->getUpdateQuery();
    }

    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    foreach ($items as $id => $item) {
      /** @var \Solarium\QueryType\Update\Query\Document\Document $doc */
      $doc = $update_query->createDocument();
      $doc->setField('id', $this->createId($index_id, $id));
      $doc->setField('index_id', $index_id);

      // Add document level boost from Search API item.
      if ($boost = $item->getBoost()) {
        $doc->setBoost($boost);
      }

      // Add the site hash and language-specific base URL.
      $doc->setField('hash', SearchApiSolrUtility::getSiteHash());
      $lang = $item->getLanguage();
      if (empty($base_urls[$lang])) {
        $url_options = array('absolute' => TRUE);
        if (isset($languages[$lang])) {
          $url_options['language'] = $languages[$lang];
        }
        // An exception is thrown if this is called during a non-HTML response
        // like REST or a redirect without collecting metadata. Avoid that by
        // collecting and discarding it.
        // See https://www.drupal.org/node/2638686.
        $base_urls[$lang] = Url::fromRoute('<front>', array(), $url_options)->toString(TRUE)->getGeneratedUrl();
      }
      $doc->setField('site', $base_urls[$lang]);
      $item_fields = $item->getFields();
      $item_fields += $special_fields = $this->getSpecialFields($index, $item);
      /** @var \Drupal\search_api\Item\FieldInterface $field */
      foreach ($item_fields as $name => $field) {
        // If the field is not known for the index, something weird has
        // happened. We refuse to index the items and hope that the others are
        // OK.
        if (!isset($field_names[$name])) {
          $vars = array(
            '%field' => $name,
            '@id' => $id,
          );
          \Drupal::logger('search_api_solr')->warning('Error while indexing: Unknown field %field on the item with ID @id.', $vars);
          $doc = NULL;
          break;
        }
        $this->addIndexField($doc, $field_names[$name], $field->getValues(), $field->getType());
        // Enable sorts in some special cases.
        if (!array_key_exists($name, $special_fields) && version_compare($schema_version, '4.4', '>=')) {
          $values = $field->getValues();
          $first_value = reset($values);
          if ($first_value) {
            // Truncate the string to avoid Solr string field limitation.
            // @see https://www.drupal.org/node/2809429
            // @see https://www.drupal.org/node/2852606
            // 32 characters should be enough for sorting and it makes no sense
            // to heavily increase the index size. The DB backend limits the
            // sort strings to 32 characters, too.
            if ($first_value instanceof TextValue && Unicode::strlen($first_value->getText()) > 32) {
              $first_value = new TextValue(Unicode::truncate($first_value->getText(), 32));
            }
            if (strpos($field_names[$name], 't') === 0 || strpos($field_names[$name], 's') === 0) {
              // Always copy fulltext fields to a dedicated field for faster
              // alpha sorts. Copy strings as well to normalize them.
              $this->addIndexField($doc, 'sort_' . $name, [$first_value], $field->getType());
            }
            elseif (preg_match('/^([a-z]+)m(_.*)/', $field_names[$name], $matches)) {
              // For other multi-valued fields (which aren't sortable by nature)
              // we use the same hackish workaround like the DB backend: just
              // copy the first value in a single value field for sorting.
              $values = $field->getValues();
              $this->addIndexField($doc, $matches[1] . 's' . $matches[2], [$first_value], $field->getType());
            }
          }
        }
      }

      if ($doc) {
        $documents[] = $doc;
      }
    }

    // Let other modules alter documents before sending them to solr.
    $this->moduleHandler->alter('search_api_solr_documents', $documents, $index, $items);
    $this->alterSolrDocuments($documents, $index, $items);

    return $documents;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $ids) {
    try {
      $index_id = $this->getIndexId($index->id());
      $solr_ids = array();
      foreach ($ids as $id) {
        $solr_ids[] = $this->createId($index_id, $id);
      }
      $connector = $this->getSolrConnector();
      $update_query = $connector->getUpdateQuery();
      $update_query->addDeleteByIds($solr_ids);
      $connector->update($update_query);
    }
    catch (ExceptionInterface $e) {
      throw new SearchApiSolrException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL) {
    // Since the index ID we use for indexing can contain arbitrary
    // prefixes, we have to escape it for use in the query.
    $connector = $this->getSolrConnector();
    $query_helper = $connector->getQueryHelper();
    $query = '+index_id:' . $this->getIndexId($query_helper->escapePhrase($index->id()));
    $query .= ' +hash:' . $query_helper->escapePhrase(SearchApiSolrUtility::getSiteHash());
    if ($datasource_id) {
      $query .= ' +' . $this->getSolrFieldNames($index)['search_api_datasource'] . ':' . $query_helper->escapePhrase($datasource_id);
    }
    $update_query = $connector->getUpdateQuery();
    $update_query->addDeleteQuery($query);
    $connector->update($update_query);
  }

  /**
   * {@inheritdoc}
   *
   * Options on $query prefixed by 'solr_param_' will be passed natively to Solr
   * as query parameter without the prefix. For example you can set the "Minimum
   * Should Match" parameter 'mm' to '75%' like this:
   * @code
   *   $query->setOption('solr_param_mm', '75%');
   * @endcode
   */
  public function search(QueryInterface $query) {
    $mlt_options = $query->getOption('search_api_mlt');
    if (!empty($mlt_options)) {
      $query->addTag('mlt');
    }

    // Call an object oriented equivalent to hook_search_api_query_alter().
    $this->alterSearchApiQuery($query);

    // Get field information.
    /** @var \Drupal\search_api\Entity\Index $index */
    $index = $query->getIndex();
    $index_id = $this->getIndexId($index->id());
    $field_names = $this->getSolrFieldNames($index);

    $connector = $this->getSolrConnector();
    $solarium_query = NULL;
    $index_fields = $index->getFields();
    $index_fields += $this->getSpecialFields($index);
    if ($query->hasTag('mlt')) {
      $solarium_query = $this->getMoreLikeThisQuery($query, $index_id, $index_fields, $field_names);
    }
    else {
      // Instantiate a Solarium select query.
      $solarium_query = $connector->getSelectQuery();

      // Extract keys.
      $keys = $query->getKeys();
      if (is_array($keys)) {
        $keys = $this->flattenKeys($keys);
      }

      if (!empty($keys)) {
        // Set them.
        $solarium_query->setQuery($keys);
      }

      // Set searched fields.
      $search_fields = $this->getQueryFulltextFields($query);
      $query_fields = [];
      $query_fields_boosted = [];
      foreach ($search_fields as $search_field) {
        $query_fields[] = $field_names[$search_field];
        /** @var \Drupal\search_api\Item\FieldInterface $field */
        $field = $index_fields[$search_field];
        $boost = $field->getBoost() ? '^' . $field->getBoost() : '';
        $query_fields_boosted[] = $field_names[$search_field] . $boost;
      }
      $solarium_query->getEDisMax()
        ->setQueryFields(implode(' ', $query_fields_boosted));

      // Set highlighting and excerpt.
      $this->setHighlighting($solarium_query, $query, $query_fields);
    }

    $options = $query->getOptions();

    // Set basic filters.
    $filter_queries = $this->getFilterQueries($query, $field_names, $index_fields, $options);
    foreach ($filter_queries as $id => $filter_query) {
      $solarium_query->createFilterQuery('filters_' . $id)
        ->setQuery($filter_query['query'])
        ->addTags($filter_query['tags']);
    }

    $query_helper = $connector->getQueryHelper($solarium_query);
    // Set the Index filter.
    $solarium_query->createFilterQuery('index_id')->setQuery('index_id:' . $query_helper->escapePhrase($index_id));

    // Set the site hash filter, if enabled.
    if ($this->configuration['site_hash']) {
      $site_hash = $query_helper->escapePhrase(SearchApiSolrUtility::getSiteHash());
      $solarium_query->createFilterQuery('site_hash')->setQuery('hash:' . $site_hash);
    }

    // @todo Make this more configurable so that Search API can choose which
    //   fields it wants to fetch. But don't skip the minimum required fields as
    //   currently set in the "else" path.
    //   @see https://www.drupal.org/node/2880674
    if (!empty($this->configuration['retrieve_data'])) {
      $solarium_query->setFields(['*', 'score']);
    }
    else {
      $returned_fields = [$field_names['search_api_id'], $field_names['search_api_language'], $field_names['search_api_relevance']];
      if (!$this->configuration['site_hash']) {
        $returned_fields[] = 'hash';
      }
      $solarium_query->setFields($returned_fields);
    }

    // Set sorts.
    $this->setSorts($solarium_query, $query, $field_names);

    // Set facet fields. setSpatial() might add more facets.
    $this->setFacets($query, $solarium_query, $field_names);

    // Handle spatial filters.
    if (isset($options['search_api_location'])) {
      $this->setSpatial($solarium_query, $options['search_api_location'], $field_names);
    }

    // Handle spatial filters.
    if (isset($options['search_api_rpt'])) {
      if (version_compare($connector->getSolrVersion(), 5.1, '>=')) {
        $this->setRpt($solarium_query, $options['search_api_rpt'], $field_names);
      }
      else {
        \Drupal::logger('search_api_solr')->error('Rpt data type feature is only supported by Solr version 5.1 or higher.');
      }

    }

    // Handle field collapsing / grouping.
    $grouping_options = $query->getOption('search_api_grouping');
    if (!empty($grouping_options['use_grouping'])) {
      $this->setGrouping($solarium_query, $query, $grouping_options, $index_fields, $field_names);
    }

    if (isset($options['offset'])) {
      $solarium_query->setStart($options['offset']);
    }
    $rows = isset($options['limit']) ? $options['limit'] : 1000000;
    $solarium_query->setRows($rows);

    if (!empty($options['search_api_spellcheck'])) {
      $solarium_query->getSpellcheck();
    }

    foreach ($options as $option => $value) {
      if (strpos($option, 'solr_param_') === 0) {
        $solarium_query->addParam(substr($option, 11), $value);
      }
    }

    $this->applySearchWorkarounds($solarium_query, $query);

    try {
      // Allow modules to alter the solarium query.
      $this->moduleHandler->alter('search_api_solr_query', $solarium_query, $query);
      $this->preQuery($solarium_query, $query);

      // Send search request.
      $response = $connector->search($solarium_query);
      $body = $response->getBody();
      if (200 != $response->getStatusCode()) {
        throw new SearchApiSolrException(strip_tags($body), $response->getStatusCode());
      }
      $this->alterSolrResponseBody($body, $query);
      $response = new Response($body, $response->getHeaders());

      $result = $connector->createSearchResult($solarium_query, $response);

      // Extract results.
      $results = $this->extractResults($query, $result);

      // Add warnings, if present.
      if (!empty($warnings)) {
        foreach ($warnings as $warning) {
          $results->addWarning($warning);
        }
      }

      // Extract facets.
      if ($result instanceof Result) {
        if ($facets = $this->extractFacets($query, $result, $field_names)) {
          $results->setExtraData('search_api_facets', $facets);
        }
      }

      $this->moduleHandler->alter('search_api_solr_search_results', $results, $query, $result);
      $this->postQuery($results, $query, $result);
    }
    catch (\Exception $e) {
      throw new SearchApiSolrException($this->t('An error occurred while trying to search with Solr: @msg.', array('@msg' => $e->getMessage())), $e->getCode(), $e);
    }
  }

  /**
   * Apply workarounds for special Solr versions before searching.
   *
   * @param \Solarium\QueryType\Select\Query\Query $solarium_query
   *   The Solarium select query object.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The \Drupal\search_api\Query\Query object representing the executed
   *   search query.
   */
  protected function applySearchWorkarounds(Query $solarium_query, QueryInterface $query) {
    // Do not modify 'Server index status' queries.
    // @see https://www.drupal.org/node/2668852
    if ($query->hasTag('server_index_status')) {
      return;
    }

    $connector = $this->getSolrConnector();
    $schema_version = $connector->getSchemaVersion();
    $solr_version = $connector->getSolrVersion();

    // Schema versions before 4.4 set the default query operator to 'AND'. But
    // incompatibilities since Solr 5.5.0 required a new query builder that
    // bases on 'OR'.
    // @see https://www.drupal.org/node/2724117
    if (version_compare($schema_version, '4.4', '<')) {
      $params = $solarium_query->getParams();
      if (!isset($params['q.op'])) {
        $solarium_query->addParam('q.op', 'OR');
      }
    }
  }

  /**
   * Creates an ID used as the unique identifier at the Solr server.
   *
   * This has to consist of both index and item ID. Optionally, the site hash is
   * also included.
   *
   * @see \Drupal\search_api_solr\Utility\Utility::getSiteHash()
   */
  protected function createId($index_id, $item_id) {
    return SearchApiSolrUtility::getSiteHash() . "-$index_id-$item_id";
  }

  /**
   * {@inheritdoc}
   */
  public function getSolrFieldNames(IndexInterface $index, $reset = FALSE) {
    // @todo The field name mapping should be cached per index because custom
    //   queries needs to access it on every query. But we need to be aware of
    //   datasource additions and deletions.
    if (!isset($this->fieldNames[$index->id()]) || $reset) {
      // This array maps "local property name" => "solr doc property name".
      $ret = array(
        'search_api_relevance' => 'score',
        'search_api_random' => 'random',
      );

      // Add the names of any fields configured on the index.
      $fields = $index->getFields();
      $fields += $this->getSpecialFields($index);
      foreach ($fields as $key => $field) {
        if (empty($ret[$key])) {
          // Generate a field name; this corresponds with naming conventions in
          // our schema.xml.
          $type = $field->getType();
          $type_info = SearchApiSolrUtility::getDataTypeInfo($type);
          $pref = isset($type_info['prefix']) ? $type_info['prefix'] : '';
          if ($this->fieldsHelper->isFieldIdReserved($key)) {
            $pref .= 's';
          }
          else {
            if ($field->getDataDefinition()->isList() || $this->isHierarchicalField($field)) {
              $pref .= 'm';
            }
            else {
              try {
                $datasource = $field->getDatasource();
                if (!$datasource) {
                  throw new SearchApiException();
                }
                else {
                  $pref .= $this->getPropertyPathCardinality($field->getPropertyPath(), $datasource->getPropertyDefinitions()) != 1 ? 'm' : 's';
                }
              }
              catch (SearchApiException $e) {
                // Thrown by $field->getDatasource(). Assume multi value to be
                // safe.
                $pref .= 'm';
              }
            }
          }
          $name = $pref . '_' . $key;
          $ret[$key] = SearchApiSolrUtility::encodeSolrName($name);

          // Add a distance pseudo field for any location field. These fields
          // don't really exist in the solr core, but we tell solr to name the
          // distance calculation results that way. Later we directly pass these
          // as "fields" to Drupal and especially Views.
          if ($type == 'location') {
            $ret[$key . '__distance'] = SearchApiSolrUtility::encodeSolrName($name . '__distance');
          }
        }
      }

      // Let modules adjust the field mappings.
      $this->moduleHandler->alter('search_api_solr_field_mapping', $index, $ret);

      $this->fieldNames[$index->id()] = $ret;
    }

    return $this->fieldNames[$index->id()];
  }

  /**
   * Computes the cardinality of a complete property path.
   *
   * @param string $property_path
   *   The property path of the property.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   The properties which form the basis for the property path.
   * @param int $cardinality
   *   The cardinality of the property path so far (for recursion).
   *
   * @return int
   *   The cardinality.
   */
  protected function getPropertyPathCardinality($property_path, array $properties, $cardinality = 1) {
    list($key, $nested_path) = SearchApiUtility::splitPropertyPath($property_path, FALSE);
    if (isset($properties[$key])) {
      $property = $properties[$key];
      if ($property instanceof FieldDefinitionInterface) {
        $storage = $property->getFieldStorageDefinition();
        if ($storage instanceof FieldStorageDefinitionInterface) {
          if ($storage->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
            // Shortcut. We reached the maximum.
            return FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
          }
          $cardinality *= $storage->getCardinality();
        }
      }

      if (isset($nested_path)) {
        $property = $this->fieldsHelper->getInnerProperty($property);
        if ($property instanceof ComplexDataDefinitionInterface) {
          $cardinality = $this->getPropertyPathCardinality($nested_path, $this->fieldsHelper->getNestedProperties($property), $cardinality);
        }
      }
    }
    return $cardinality;
  }

  /**
   * Checks if a field is (potentially) hierarchical.
   *
   * Fields are (potentially) hierarchical if:
   * - they point to an entity type; and
   * - that entity type contains a property referencing the same type of entity
   *   (so that a hierarchy could be built from that nested property).
   *
   * @see \Drupal\search_api\Plugin\search_api\processor\AddHierarchy::getHierarchyFields()
   *
   * @return bool
   */
  protected function isHierarchicalField(FieldInterface $field) {
    $definition = $field->getDataDefinition();
    if ($definition instanceof ComplexDataDefinitionInterface) {
      $properties = $this->fieldsHelper->getNestedProperties($definition);
      // The property might be an entity data definition itself.
      $properties[''] = $definition;
      foreach ($properties as $property) {
        $property = $this->fieldsHelper->getInnerProperty($property);
        if ($property instanceof EntityDataDefinitionInterface) {
          if ($this->hasHierarchicalProperties($property)) {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Checks if hierarchical properties are nested on an entity-typed property.
   *
   * @see \Drupal\search_api\Plugin\search_api\processor\AddHierarchy::findHierarchicalProperties()
   *
   * @param \Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface $property
   *   The property to be searched for hierarchical nested properties.
   *
   * @return bool
   */
  protected function hasHierarchicalProperties(EntityDataDefinitionInterface $property) {
    $entity_type_id = $property->getEntityTypeId();

    // Check properties for potential hierarchy. Check two levels down, since
    // Core's entity references all have an additional "entity" sub-property for
    // accessing the actual entity reference, which we'd otherwise miss.
    foreach ($this->fieldsHelper->getNestedProperties($property) as $name_2 => $property_2) {
      $property_2 = $this->fieldsHelper->getInnerProperty($property_2);
      if ($property_2 instanceof EntityDataDefinitionInterface) {
        if ($property_2->getEntityTypeId() == $entity_type_id) {
          return TRUE;
        }
      }
      elseif ($property_2 instanceof ComplexDataDefinitionInterface) {
        foreach ($property_2->getPropertyDefinitions() as $property_3) {
          $property_3 = $this->fieldsHelper->getInnerProperty($property_3);
          if ($property_3 instanceof EntityDataDefinitionInterface) {
            if ($property_3->getEntityTypeId() == $entity_type_id) {
              return TRUE;
            }
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Helper method for indexing.
   *
   * Adds $value with field name $key to the document $doc. The format of $value
   * is the same as specified in
   * \Drupal\search_api\Backend\BackendSpecificInterface::indexItems().
   */
  protected function addIndexField(Document $doc, $key, array $values, $type) {
    // Don't index empty values (i.e., when field is missing).
    if (!isset($values)) {
      return;
    }

    // All fields.
    foreach ($values as $value) {
      switch ($type) {
        case 'boolean':
          $value = $value ? 'true' : 'false';
          break;

        case 'date':
          $value = $this->formatDate($value);
          if ($value === FALSE) {
            return;
          }
          break;

        case 'integer':
          $value = (int) $value;
          break;

        case 'decimal':
          $value = (float) $value;
          break;

        case 'text':
          /** @var \Drupal\search_api\Plugin\search_api\data_type\value\TextValueInterface $value */
          /*
          $tokens = $value->getTokens();
          if (is_array($tokens)) {
            foreach ($tokens as $token) {
              // @todo handle token boosts
              // @see https://www.drupal.org/node/2746263
              #$doc->addField($boost_key, $tokenized_value['value'], $tokenized_value['score']);
              $token->getText();
              $token->getBoost();
            }
          }
          */
          $value = $value->toText();
          break;

        case 'string':
        default:
          // Keep $value as it is.
      }

      $doc->addField($key, $value);
    }
  }

  /**
   * Applies custom modifications to indexed Solr documents.
   *
   * This method allows subclasses to easily apply custom changes before the
   * documents are sent to Solr. The method is empty by default.
   *
   * @param \Solarium\QueryType\Update\Query\Document\Document[] $documents
   *   An array of \Solarium\QueryType\Update\Query\Document\Document objects
   *   ready to be indexed, generated from $items array.
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index for which items are being indexed.
   * @param array $items
   *   An array of items being indexed.
   *
   * @see hook_search_api_solr_documents_alter()
   */
  protected function alterSolrDocuments(array &$documents, IndexInterface $index, array $items) {
  }

  /**
   * Extract results from a Solr response.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The Search API query object.
   * @param \Solarium\Core\Query\Result\ResultInterface $result
   *   A Solarium select response object.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface
   *   A result set object.
   */
  protected function extractResults(QueryInterface $query, ResultInterface $result) {
    $index = $query->getIndex();
    $backend_config = $index->getServerInstance()->getBackendConfig();
    $field_names = $this->getSolrFieldNames($index);
    $fields = $index->getFields(TRUE);
    $site_hash = SearchApiSolrUtility::getSiteHash();
    // We can find the item ID and the score in the special 'search_api_*'
    // properties. Mappings are provided for these properties in
    // SearchApiSolrBackend::getFieldNames().
    $id_field = $field_names['search_api_id'];
    $score_field = $field_names['search_api_relevance'];
    $language_field = $field_names['search_api_language'];

    // Set up the results array.
    $result_set = $query->getResults();
    $result_set->setExtraData('search_api_solr_response', $result->getData());

    // In some rare cases (e.g., MLT query with nonexistent ID) the response
    // will be NULL.
    $is_grouping = $result instanceof Result && $result->getGrouping();
    if (!$result->getResponse() && !$is_grouping) {
      $result_set->setResultCount(0);
      return $result_set;
    }

    // If field collapsing has been enabled for this query, we need to process
    // the results differently.
    $grouping = $query->getOption('search_api_grouping');
    $docs = array();
    if (!empty($grouping['use_grouping']) && $is_grouping) {
      // $docs = array();
      //      $result_set['result count'] = 0;
      //      foreach ($grouping['fields'] as $field) {
      //        if (!empty($response->grouped->{$fields[$field]})) {
      //          $result_set['result count'] += $response->grouped->{$fields[$field]}->ngroups;
      //          foreach ($response->grouped->{$fields[$field]}->groups as $group) {
      //            foreach ($group->doclist->docs as $doc) {
      //              $docs[] = $doc;
      //            }
      //          }
      //        }
      //      }.
    }
    else {
      $result_set->setResultCount($result->getNumFound());
      $docs = $result->getDocuments();
    }

    // Add each search result to the results array.
    /** @var \Solarium\QueryType\Select\Result\Document $doc */
    foreach ($docs as $doc) {
      $doc_fields = $doc->getFields();
      $item_id = $doc_fields[$id_field];
      // For items coming from a different site, we need to adapt the item ID.
      if (!$this->configuration['site_hash'] && $doc_fields['hash'] != $site_hash) {
        $item_id = $doc_fields['hash'] . '--' . $item_id;
      }
      $result_item = $this->fieldsHelper->createItem($index, $item_id);
      $result_item->setExtraData('search_api_solr_document', $doc);
      $result_item->setLanguage($doc_fields[$language_field]);

      if(isset($doc_fields[$score_field])) {
        $result_item->setScore($doc_fields[$score_field]);
        unset($doc_fields[$score_field]);
      }
      // The language field should not be removed. We keep it in the values as
      // well for backward compatibility and for easy access.
      unset($doc_fields[$id_field]);

      // Extract properties from the Solr document, translating from Solr to
      // Search API property names. This reverses the mapping in
      // SearchApiSolrBackend::getFieldNames().
      foreach ($field_names as $search_api_property => $solr_property) {
        if (isset($doc_fields[$solr_property]) && isset($fields[$search_api_property])) {
          $doc_field = is_array($doc_fields[$solr_property]) ? $doc_fields[$solr_property] : [$doc_fields[$solr_property]];
          $field = clone $fields[$search_api_property];
          foreach ($doc_field as &$value) {
            switch ($field->getType()) {
              case 'date':
                // Field type convertions
                // Date fields need some special treatment to become valid date values
                // (i.e., timestamps) again.
                if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value)) {
                  $value = strtotime($value);
                }
                break;

              case 'text':
                $value = new TextValue($value);
            }
          }
          $field->setValues($doc_field);
          $result_item->setField($search_api_property, $field);
        }
      }

      $solr_id = $this->createId($index->id(), $result_item->getId());
      if ($excerpt = $this->getExcerpt($result->getData(), $solr_id, $result_item, $field_names)) {
        $result_item->setExcerpt($excerpt);
      }

      $result_set->addResultItem($result_item);
    }

    // Check for spellcheck suggestions.
    /*if (module_exists('search_api_spellcheck') && $query->getOption('search_api_spellcheck')) {
       $result_set->setExtraData('search_api_spellcheck', new SearchApiSpellcheckSolr($result));
    }*/

    return $result_set;
  }

  /**
   * Extracts facets from a Solarium result set.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query.
   * @param \Solarium\QueryType\Select\Result\Result $resultset
   *   A Solarium select response object.
   *
   * @return array
   *   An array describing facets that apply to the current results.
   */
  protected function extractFacets(QueryInterface $query, Result $resultset, array $field_names) {
    if (!$resultset->getFacetSet()) {
      return [];
    }

    $facets = [];
    $index = $query->getIndex();
    $fields = $index->getFields();

    $extract_facets = $query->getOption('search_api_facets', []);

    if ($facet_fields = $resultset->getFacetSet()->getFacets()) {
      foreach ($extract_facets as $delta => $info) {
        $field = $field_names[$info['field']];
        if (!empty($facet_fields[$field])) {
          $min_count = $info['min_count'];
          $terms = $facet_fields[$field]->getValues();
          if ($info['missing']) {
            // We have to correctly incorporate the "_empty_" term.
            // This will ensure that the term with the least results is dropped,
            // if the limit would be exceeded.
            if (isset($terms[''])) {
              if ($terms[''] < $min_count) {
                unset($terms['']);
              }
              else {
                arsort($terms);
                if ($info['limit'] > 0 && count($terms) > $info['limit']) {
                  array_pop($terms);
                }
              }
            }
          }
          elseif (isset($terms[''])) {
            unset($terms['']);
          }
          $type = isset($fields[$info['field']]) ? $fields[$info['field']]->getType() : 'string';
          foreach ($terms as $term => $count) {
            if ($count >= $min_count) {
              if ($term === '') {
                $term = '!';
              }
              elseif ($type == 'boolean') {
                if ($term == 'true') {
                  $term = '"1"';
                }
                elseif ($term == 'false') {
                  $term = '"0"';
                }
              }
              elseif ($type == 'date') {
                $term = $term ? '"' . strtotime($term) . '"' : NULL;
              }
              else {
                $term = "\"$term\"";
              }
              if ($term) {
                $facets[$delta][] = array(
                  'filter' => $term,
                  'count' => $count,
                );
              }
            }
          }
          if (empty($facets[$delta])) {
            unset($facets[$delta]);
          }
        }
      }
    }

    $result_data = $resultset->getData();
    if (isset($result_data['facet_counts']['facet_queries'])) {
      if ($spatials = $query->getOption('search_api_location')) {
        foreach ($result_data['facet_counts']['facet_queries'] as $key => $count) {
          // This special key is defined in setSpatial().
          if (!preg_match('/^spatial-(.*)-(\d+(?:\.\d+)?)-(\d+(?:\.\d+)?)$/', $key, $matches)) {
            continue;
          }
          if (empty($extract_facets[$matches[1]])) {
            continue;
          }
          $facet = $extract_facets[$matches[1]];
          if ($count >= $facet['min_count']) {
            $facets[$matches[1]][] = [
              'filter' => "[{$matches[2]} {$matches[3]}]",
              'count' => $count,
            ];
          }
        }
      }
    }
    // Extract heatmaps.
    if (isset($result_data['facet_counts']['facet_heatmaps'])) {
      if ($spatials = $query->getOption('search_api_rpt')) {
        foreach ($result_data['facet_counts']['facet_heatmaps'] as $key => $value) {
          if (!preg_match('/^rpts_(.*)$/', $key, $matches)) {
            continue;
          }
          if (empty($extract_facets[$matches[1]])) {
            continue;
          }
          $heatmaps = array_slice($value, 15);
          array_walk_recursive($heatmaps, function ($heatmaps) use (&$heatmap) {
            $heatmap[] = $heatmaps;
          });
          $count = array_sum($heatmap);
          $facets[$matches[1]][] = [
            'filter' => $value,
            'count' => $count,
          ];
        }
      }
    }

    return $facets;
  }

  /**
   * Adds item language conditions to the condition group, if applicable.
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $condition_group
   *   The condition group on which to set conditions.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to inspect for language settings.
   *
   * @see \Drupal\search_api\Query\QueryInterface::getLanguages()
   */
  protected function addLanguageConditions(ConditionGroupInterface $condition_group, QueryInterface $query) {
    $languages = $query->getLanguages();
    if ($languages !== NULL) {
      $condition_group->addCondition('search_api_language', $languages, 'IN');
    }
  }

  /**
   * Serializes a query's conditions as Solr filter queries.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to get the conditions from.
   * @param array $solr_fields
   *   The mapping from Drupal to Solr field names.
   * @param \Drupal\search_api\Item\FieldInterface[] $index_fields
   *   The fields handled by the curent index.
   * @param array $options
   *   The query options.
   *
   * @return array
   *   Array of filter query strings.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  protected function getFilterQueries(QueryInterface $query, array $solr_fields, array $index_fields, array &$options) {
    $condition_group = $query->getConditionGroup();
    $this->addLanguageConditions($condition_group, $query);
    return $this->createFilterQueries($condition_group, $solr_fields, $index_fields, $options);
  }

  /**
   * Recursively transforms conditions into a flat array of Solr filter queries.
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $condition_group
   *   The group of conditions.
   * @param array $solr_fields
   *   The mapping from Drupal to Solr field names.
   * @param \Drupal\search_api\Item\FieldInterface[] $index_fields
   *   The fields handled by the curent index.
   * @param array $options
   *   The query options.
   *
   * @return array
   *   Array of filter query strings.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  protected function createFilterQueries(ConditionGroupInterface $condition_group, array $solr_fields, array $index_fields, array &$options) {
    $fq = [];

    $conditions = $condition_group->getConditions();
    foreach ($conditions as $condition) {
      if ($condition instanceof ConditionInterface) {
        // Nested condition.
        $field = $condition->getField();
        if (!isset($index_fields[$field])) {
          throw new SearchApiException($this->t('Filter term on unknown or unindexed field @field.', array('@field' => $field)));
        }
        $value = $condition->getValue();
        $filter_query = $this->createFilterQuery($solr_fields[$field], $value, $condition->getOperator(), $index_fields[$field], $options);
        if ($filter_query) {
          $fq[] = [
            'query' => $this->createFilterQuery($solr_fields[$field], $value, $condition->getOperator(), $index_fields[$field], $options),
            'tags' => $condition_group->getTags(),
          ];
        }
      }
      else {
        // Nested condition group.
        $nested_fqs = $this->createFilterQueries($condition, $solr_fields, $index_fields, $options);
        $fq = array_merge($fq, $this->reduceFilterQueries($nested_fqs, $condition));
      }
    }

    return $fq;
  }

  /**
   * Reduces an array of filter queries to an array containing one filter query.
   *
   * The queries will be logically combined and their tags will be merged.
   *
   * @param array $filter_queries
   * @param \Drupal\search_api\Query\ConditionGroupInterface $condition_group
   * @param bool $last
   *
   * @return array
   */
  protected function reduceFilterQueries(array $filter_queries, ConditionGroupInterface $condition_group, $last = FALSE) {
    $fq = [];
    if (count($filter_queries) > 1) {
      $queries = [];
      $tags = [];
      $pre = $condition_group->getConjunction() == 'OR' ? '' : '+';
      foreach ($filter_queries as $nested_fq) {
        if (strpos($nested_fq['query'], '-') !== 0) {
          $queries[] = $pre . $nested_fq['query'];
        }
        elseif (!$pre) {
          $queries[] = '(' . $nested_fq['query'] . ')';
        }
        else {
          $queries[] = $nested_fq['query'];
        }
        $tags += $nested_fq['tags'];
      }
      $fq[] = [
        'query' => (!$last ? '(' : '') . implode(' ', $queries) . (!$last ? ')' : ''),
        'tags' => array_unique($tags + $condition_group->getTags()),
      ];
    }
    elseif (!empty($filter_queries)) {
      $fq[] = [
        'query' => $filter_queries[0]['query'],
        'tags' => array_unique($filter_queries[0]['tags'] + $condition_group->getTags()),
      ];
    }

    return $fq;
  }

  /**
   * Create a single search query string.
   */
  protected function createFilterQuery($field, $value, $operator, FieldInterface $index_field, array &$options) {
    if (!is_array($value)) {
      $value = [$value];
    }

    foreach ($value as &$v) {
      if (!is_null($v) || !in_array($operator, ['=', '<>', 'IN', 'NOT IN'])) {
        $v = trim($v);
        $v = $this->formatFilterValue($v, $index_field->getType());
        // Remaining NULL values are now converted to empty strings.
      }
    }
    unset($v);

    if (1 == count($value)) {
      $value = array_shift($value);

      switch ($operator) {
        case 'IN':
          $operator = '=';
          break;

        case 'NOT IN':
          $operator = '<>';
          break;
      }
    }

    if (!is_null($value) && isset($options['search_api_location'])) {
      foreach ($options['search_api_location'] as &$spatial) {
        if (!empty($spatial['field']) && $index_field->getFieldIdentifier() == $spatial['field']) {
          // Spatial filter queries need modifications to the query itself.
          // Therefor we just store the parameters an let them be handled later.
          // @see setSpatial()
          // @see createLocationFilterQuery()
          $spatial['filter_query_conditions'] = [
            'field' => $field,
            'value' => $value,
            'operator' => $operator,
          ];
          return NULL;
        }
      }
    }

    switch ($operator) {
      case '<>':
        if (is_null($value)) {
          return "$field:[* TO *]";
        }
        else {
          return "(*:* -$field:$value)";
        }

      case '<':
        return "$field:{* TO $value}";

      case '<=':
        return "$field:[* TO $value]";

      case '>=':
        return "$field:[$value TO *]";

      case '>':
        return "$field:{{$value} TO *}";

      case 'BETWEEN':
        return "$field:[" . array_shift($value) . ' TO ' . array_shift($value) . ']';

      case 'NOT BETWEEN':
        return "(*:* -$field:[" . array_shift($value) . ' TO ' . array_shift($value) . '])';

      case 'IN':
        $parts = [];
        $null = FALSE;
        foreach ($value as $v) {
          if (is_null($v)) {
            $null = TRUE;
          }
          else {
            $parts[] = "$field:$v";
          }
        }
        if ($null) {
          // @see https://stackoverflow.com/questions/4238609/how-to-query-solr-for-empty-fields/28859224#28859224
          return "(*:* -$field:[* TO *])";
        }
        return '(' . implode(" ", $parts) . ')';

      case 'NOT IN':
        $parts = [];
        $null = FALSE;
        foreach ($value as $v) {
          if (is_null($v)) {
            $null = TRUE;
          }
          else {
            $parts[] = "-$field:$v";
          }
        }
        return '(' . ($null ? "$field:[* TO *]" : '*:*') . ' ' . implode(" ", $parts) . ')';

      case '=':
      default:
        if (is_null($value)) {
          // @see https://stackoverflow.com/questions/4238609/how-to-query-solr-for-empty-fields/28859224#28859224
          return "(*:* -$field:[* TO *])";
        }
        else {
          return "$field:$value";
        }
    }
  }

  /**
   * Create a single search query string.
   */
  protected function createLocationFilterQuery(&$spatial) {
    $spatial_method = (isset($spatial['method']) && in_array($spatial['method'], ['geofilt', 'bbox'])) ? $spatial['method'] : 'geofilt';
    $value = $spatial['filter_query_conditions']['value'];

    switch ($spatial['filter_query_conditions']['operator']) {
      case '<':
      case '<=':
        $spatial['radius'] = $value;
        return '{!' . $spatial_method . '}';

      case '>':
      case '>=':
        $spatial['min_radius'] = $value;
        return "{!frange l=$value}geodist()";

      case 'BETWEEN':
        $spatial['min_radius'] = array_shift($value);
        $spatial['radius'] = array_shift($value);
        return '{!frange l=' . $spatial['min_radius']. ' u=' . $spatial['radius'] . '}geodist()';

      case '=':
      case '<>':
      case 'NOT BETWEEN':
      case 'IN':
      case 'NOT IN':
      default:
        throw new SearchApiSolrException('Unsupported operator for location queries');
    }
  }

  /**
   * Format a value for filtering on a field of a specific type.
   */
  protected function formatFilterValue($value, $type) {
    switch ($type) {
      case 'boolean':
        $value = $value ? 'true' : 'false';
        break;

      case 'date':
        $value = $this->formatDate($value);
        if ($value === FALSE) {
          return 0;
        }
        break;

      case 'location':
        // Do not escape.
        return (float) $value;
    }
    return $this->getSolrConnector()->getQueryHelper()->escapePhrase($value);
  }

  /**
   * Tries to format given date with solarium query helper.
   *
   * @param mixed $input
   *
   * @return bool|string
   */
  protected function formatDate($input) {
    $input = is_numeric($input) ? (int) $input : new \DateTime($input, timezone_open(DATETIME_STORAGE_TIMEZONE));
    return $this->getSolrConnector()->getQueryHelper()->formatDate($input);
  }

  /**
   * Helper method for creating the facet field parameters.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   * @param \Solarium\QueryType\Select\Query\Query $solarium_query
   * @param array $field_names
   */
  protected function setFacets(QueryInterface $query, Query $solarium_query, array $field_names) {
    $facets = $query->getOption('search_api_facets', []);

    if (empty($facets)) {
      return;
    }

    $facet_set = $solarium_query->getFacetSet();
    $facet_set->setSort('count');
    $facet_set->setLimit(10);
    $facet_set->setMinCount(1);
    $facet_set->setMissing(FALSE);

    foreach ($facets as $info) {
      if (empty($field_names[$info['field']])) {
        continue;
      }
      $field = $field_names[$info['field']];
      // Create the Solarium facet field object.
      $facet_field = $facet_set->createFacetField($field)->setField($field);

      // For "OR" facets, add the expected tag for exclusion.
      if (isset($info['operator']) && strtolower($info['operator']) === 'or') {
        // @see https://cwiki.apache.org/confluence/display/solr/Faceting#Faceting-LocalParametersforFaceting
        $facet_field->setExcludes(array('facet:' . $info['field']));
      }

      // Set limit, unless it's the default.
      if ($info['limit'] != 10) {
        $limit = $info['limit'] ? $info['limit'] : -1;
        $facet_field->setLimit($limit);
      }
      // Set mincount, unless it's the default.
      if ($info['min_count'] != 1) {
        $facet_field->setMinCount($info['min_count']);
      }

      // Set missing, if specified.
      if ($info['missing']) {
        $facet_field->setMissing(TRUE);
      }
      else {
        $facet_field->setMissing(FALSE);
      }
    }
  }

  /**
   * Allow custom changes before converting a SearchAPI query into a Solr query.
   *
   * This is an object oriented equivalent to hook_search_api_query_alter() to
   * avoid that any logic needs to be split between the backend class and a
   * module file.
   *
   * @see hook_search_api_query_alter()
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The \Drupal\search_api\Query\Query object.
   */
  protected function alterSearchApiQuery(QueryInterface $query) {
  }

  /**
   * Allow custom changes before sending a search query to Solr.
   *
   * This allows subclasses to apply custom changes before the query is sent to
   * Solr. Works exactly like hook_search_api_solr_query_alter().
   *
   * @param \Solarium\Core\Query\QueryInterface $solarium_query
   *   The Solarium query object.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The \Drupal\search_api\Query\Query object representing the executed
   *   search query.
   */
  protected function preQuery(SolariumQueryInterface $solarium_query, QueryInterface $query) {
  }

  /**
   * Allow custom changes before search results are returned for subclasses.
   *
   * Works exactly like hook_search_api_solr_search_results_alter().
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The results array that will be returned for the search.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The \Drupal\search_api\Query\Query object representing the executed
   *   search query.
   * @param object $response
   *   The response object returned by Solr.
   */
  protected function postQuery(ResultSetInterface $results, QueryInterface $query, $response) {
  }

  /**
   * Allow custom changes to the response body before extracting values.
   *
   * @param string $body
   * @param \Drupal\search_api\Query\QueryInterface $query
   */
  protected function alterSolrResponseBody(&$body, QueryInterface $query) {
  }

  /**
   * Implements autocomplete compatible to AutocompleteBackendInterface.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   A query representing the completed user input so far.
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   An object containing details about the search the user is on, and
   *   settings for the autocompletion. See the class documentation for details.
   *   Especially $search->options should be checked for settings, like whether
   *   to try and estimate result counts for returned suggestions.
   * @param string $incomplete_key
   *   The start of another fulltext keyword for the search, which should be
   *   completed. Might be empty, in which case all user input up to now was
   *   considered completed. Then, additional keywords for the search could be
   *   suggested.
   * @param string $user_input
   *   The complete user input for the fulltext search keywords so far.
   *
   * @return \Drupal\search_api_autocomplete\Suggestion\SuggestionInterface[]
   *   An array of suggestions.
   *
   * @see \Drupal\search_api_autocomplete\AutocompleteBackendInterface
   */
  public function getAutocompleteSuggestions(QueryInterface $query, SearchInterface $search, $incomplete_key, $user_input) {
    $suggestions = [];
    $factory = NULL;
    if (class_exists(SuggestionFactory::class)) {
      $factory = new SuggestionFactory($user_input);
    }

    if ($this->configuration['suggest_suffix'] || $this->configuration['suggest_corrections'] || $this->configuration['suggest_words']) {
      $connector = $this->getSolrConnector();
      $solr_version = $connector->getSolrVersion();
      if (version_compare($solr_version, '6.5', '=')) {
        \Drupal::logger('search_api_solr')->error('Solr 6.5.x contains a bug that breaks the autocomplete feature. Downgrade to 6.4.x or upgrade to 6.6.x.');
        return [];
      }
      $solarium_query = $connector->getTermsQuery();
      $schema_version = $connector->getSchemaVersion();
      if (version_compare($schema_version, '5.4', '>=')) {
        $solarium_query->setHandler('autocomplete');
      }
      else {
        $solarium_query->setHandler('terms');
      }

      try {
        $fl = [];
        if (version_compare($schema_version, '5.4', '>=')) {
          $fl = $this->getAutocompleteFields($query, $search);
        }
        else {
          $fl[] = 'spell';
        }

        // Make the input lowercase as the indexed data is (usually) also all
        // lowercase.
        $incomplete_key = Unicode::strtolower($incomplete_key);
        $user_input = Unicode::strtolower($user_input);

        $solarium_query->setFields($fl);
        $solarium_query->setPrefix($incomplete_key);
        $solarium_query->setLimit(10);

        if ($this->configuration['suggest_corrections']) {
          $solarium_query->addParam('q', $user_input);
          $solarium_query->addParam('spellcheck', 'true');
          $solarium_query->addParam('spellcheck.count', 1);
        }

        /** @var \Solarium\QueryType\Terms\Result $terms_result */
        $terms_result = $connector->execute($solarium_query);

        $autocomplete_terms = [];
        foreach ($terms_result as $terms) {
          foreach ($terms as $term => $count) {
            if ($term != $incomplete_key) {
              $autocomplete_terms[$term] = $count;
            }
          }
        }

        if ($this->configuration['suggest_suffix']) {
          foreach ($autocomplete_terms as $term => $count) {
            $suggestion_suffix = mb_substr($term, mb_strlen($incomplete_key));
            if ($factory) {
              $suggestions[] = $factory->createFromSuggestionSuffix($suggestion_suffix, $count);
            }
            else {
              $suggestions[] = Suggestion::fromSuggestionSuffix($suggestion_suffix, $count, $user_input);
            }
          }
        }

        if ($this->configuration['suggest_corrections']) {
          if (version_compare($schema_version, '5.4', '<')) {
            $solarium_query->setHandler('select');
            $terms_result = $connector->execute($solarium_query);
          }
          $suggestion = $user_input;
          $suggester_result = new SuggesterResult(NULL, new SuggesterQuery(), $terms_result->getResponse());
          foreach ($suggester_result as $term => $termResult) {
            foreach ($termResult as $result) {
              if ($result == $term) {
                continue;
              }
              $correction = preg_replace('@(\b)' . preg_quote($term, '@') . '(\b)@', '$1' . $result . '$2', $suggestion);
              if ($correction != $suggestion) {
                $suggestion = $correction;
                // Swapped one term. Try to correct the next term.
                break;
              }
            }
          }

          if ($suggestion != $user_input && !array_key_exists($suggestion, $autocomplete_terms)) {
            if ($factory) {
              $suggestions[] = $factory->createFromSuggestedKeys($suggestion);
            }
            else {
              $suggestions[] = Suggestion::fromSuggestedKeys($suggestion, $user_input);
            }
            foreach (array_keys($autocomplete_terms) as $term) {
              $completion = preg_replace('@(\b)' . preg_quote($incomplete_key, '@') . '$@', '$1' . $term . '$2', $suggestion);
              if ($completion != $suggestion) {
                if ($factory) {
                  $suggestions[] = $factory->createFromSuggestedKeys($completion);
                }
                else {
                  $suggestions[] = Suggestion::fromSuggestedKeys($completion, $user_input);
                }
              }
            }
          }
        }
      }
      catch (SearchApiException $e) {
        watchdog_exception('search_api_solr', $e);
        return [];
      }

    }

    return $suggestions;
  }

  /**
   * Get the fields to search for autocomplete terms.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   A query representing the completed user input so far.
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   An object containing details about the search the user is on, and
   *   settings for the autocompletion. See the class documentation for details.
   *   Especially $search->options should be checked for settings, like whether
   *   to try and estimate result counts for returned suggestions.
   *
   * @return array
   */
  protected function getAutocompleteFields(QueryInterface $query, SearchInterface $search) {
    $fl = [];
    $solr_field_names = $this->getSolrFieldNames($query->getIndex());
    $fulltext_fields = $search->getOption('fields') ? $search->getOption('fields') : $this->getQueryFulltextFields($query);
    foreach ($fulltext_fields as $fulltext_field) {
      $fl[] = 'terms_' . $solr_field_names[$fulltext_field];
    }
    return $fl;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);

    // Update the configuration of the Solr connector as well by replacing it by
    // a new instance with the latest configuration.
    $this->solrConnector = NULL;
  }

  /**
   * Prefixes an index ID as configured.
   *
   * The resulting ID will be a concatenation of the following strings:
   * - If set, the "search_api_solr.settings.index_prefix" configuration.
   * - If set, the index-specific "search_api_solr.settings.index_prefix_INDEX"
   *   configuration.
   * - The index's machine name.
   *
   * @param string $machine_name
   *   The index's machine name.
   *
   * @return string
   *   The prefixed machine name.
   */
  protected function getIndexId($machine_name) {
    // Prepend per-index prefix.
    $id = $this->searchApiSolrSettings->get('index_prefix_' . $machine_name) . $machine_name;
    // Prepend environment prefix.
    $id = $this->searchApiSolrSettings->get('index_prefix') . $id;
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->calculatePluginDependencies($this->getSolrConnector());
    return $this->dependencies;
  }

  /**
   * Extract and format highlighting information for a specific item.
   *
   * Will also use highlighted fields to replace retrieved field data, if the
   * corresponding option is set.
   *
   * @param array $data
   *   The data extracted from a Solr result.
   * @param string $solr_id
   *   The ID of the result item.
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The fields of the result item.
   * @param array $field_mapping
   *   Mapping from search_api field names to Solr field names.
   *
   * @return bool|string
   *   FALSE if no excerpt is returned from Solr, the excerpt string otherwise.
   */
  protected function getExcerpt($data, $solr_id, ItemInterface $item, array $field_mapping) {
    if (!isset($data['highlighting'][$solr_id])) {
      return FALSE;
    }
    $output = '';
    // @todo using the spell field is not the optimal solution.
    // @see https://www.drupal.org/node/2735881
    if (!empty($this->configuration['excerpt']) && !empty($data['highlighting'][$solr_id]['spell'])) {
      foreach ($data['highlighting'][$solr_id]['spell'] as $snippet) {
        $snippet = strip_tags($snippet);
        $snippet = preg_replace('/^.*>|<.*$/', '', $snippet);
        $snippet = SearchApiSolrUtility::formatHighlighting($snippet);
        // The created fragments sometimes have leading or trailing punctuation.
        // We remove that here for all common cases, but take care not to remove
        // < or > (so HTML tags stay valid).
        $snippet = trim($snippet, "\00..\x2F:;=\x3F..\x40\x5B..\x60");
        $output .= $snippet . ' … ';
      }
    }
    if (!empty($this->configuration['highlight_data'])) {
      $item_fields = $item->getFields();
      foreach ($field_mapping as $search_api_property => $solr_property) {
        if (!empty($data['highlighting'][$solr_id][$solr_property])) {
          $snippets = [];
          foreach ($data['highlighting'][$solr_id][$solr_property] as $value) {
            // Contrary to above, we here want to preserve HTML, so we just
            // replace the [HIGHLIGHT] tags with the appropriate format.
            $snippets[] = [
              'raw' => preg_replace('#\[(/?)HIGHLIGHT\]#', '', $value),
              'replace' => SearchApiSolrUtility::formatHighlighting($value),
            ];
          }
          if ($snippets) {
            $values = $item_fields[$search_api_property]->getValues();
            foreach ($values as $value) {
              foreach ($snippets as $snippet) {
                if ($value instanceof TextValue) {
                  if ($value->getText() === $snippet['raw']) {
                    $value->setText($snippet['replace']);
                  }
                }
                else {
                  if ($value == $snippet['raw']) {
                    $value = $snippet['replace'];
                  }
                }
              }
            }
            $item_fields[$search_api_property]->setValues($values);
          }
        }
      }
    }

    return $output;
  }

  /**
   * Flatten a keys array into a single search string.
   *
   * @param array $keys
   *   The keys array to flatten, formatted as specified by
   *   \Drupal\search_api\Query\QueryInterface::getKeys().
   *
   * @return string
   *   A Solr query string representing the same keys.
   */
  protected function flattenKeys(array $keys) {
    $k = [];
    $pre = '+';

    if(isset($keys['#conjunction']) && $keys['#conjunction'] == 'OR') {
      $pre = '';
    }

    $neg = empty($keys['#negation']) ? '' : '-';

    foreach ($keys as $key_nr => $key) {
      // We cannot use \Drupal\Core\Render\Element::children() anymore because
      // $keys is not a valid render array.
      if ($key_nr[0] === '#' || !$key) {
        continue;
      }
      if (is_array($key)) {
        $subkeys = $this->flattenKeys($key);
        if ($subkeys) {
          $nested_expressions = TRUE;
          $k[] = "($subkeys)";
        }
      }
      else {
        $k[] = $this->getSolrConnector()->getQueryHelper()->escapePhrase(trim($key));
      }
    }
    if (!$k) {
      return '';
    }

    // Formatting the keys into a Solr query can be a bit complex. Keep in mind
    // that the default operator is OR. The following code will produce filters
    // that look like this:
    //
    // #conjunction | #negation | return value
    // ----------------------------------------------------------------
    // AND          | FALSE     | (+A +B +C)
    // AND          | TRUE      | -(+A +B +C)
    // OR           | FALSE     | (A B C)
    // OR           | TRUE      | -(A B C)
    //
    // If there was just a single, unnested key, we can ignore all this.
    if (count($k) == 1 && empty($nested_expressions)) {
      return $neg . reset($k);
    }

    return $neg . '(' . $pre . implode(' ' . $pre, $k) . ')';
  }

  /**
   * Sets the highlighting parameters.
   *
   * (The $query parameter currently isn't used and only here for the potential
   * sake of subclasses.)
   *
   * @param \Solarium\QueryType\Select\Query\Query $solarium_query
   *   The Solarium select query object.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query object.
   * @param array $query_fields
   *   The solr fields to be highlighted.
   */
  protected function setHighlighting(Query $solarium_query, QueryInterface $query, $highlighted_fields = []) {
    $excerpt = !empty($this->configuration['excerpt']);
    $highlight = !empty($this->configuration['highlight_data']);

    if ($highlight || $excerpt) {
      $highlighter = \Drupal::config('search_api_solr.standard_highlighter');

      $hl = $solarium_query->getHighlighting();
      $hl->setSimplePrefix('[HIGHLIGHT]');
      $hl->setSimplePostfix('[/HIGHLIGHT]');
      if ($highlighter->get('maxAnalyzedChars') != $highlighter->getOriginal('maxAnalyzedChars')) {
        $hl->setMaxAnalyzedChars($highlighter->get('maxAnalyzedChars'));
      }
      if ($highlighter->get('fragmenter') != $highlighter->getOriginal('fragmenter')) {
        $hl->setFragmenter($highlighter->get('fragmenter'));
      }
      if ($highlighter->get('usePhraseHighlighter') != $highlighter->getOriginal('usePhraseHighlighter')) {
        $hl->setUsePhraseHighlighter($highlighter->get('usePhraseHighlighter'));
      }
      if ($highlighter->get('highlightMultiTerm') != $highlighter->getOriginal('highlightMultiTerm')) {
        $hl->setHighlightMultiTerm($highlighter->get('highlightMultiTerm'));
      }
      if ($highlighter->get('preserveMulti') != $highlighter->getOriginal('preserveMulti')) {
        $hl->setPreserveMulti($highlighter->get('preserveMulti'));
      }
      if ($highlighter->get('regex.slop') != $highlighter->getOriginal('regex.slop')) {
        $hl->setRegexSlop($highlighter->get('regex.slop'));
      }
      if ($highlighter->get('regex.pattern') != $highlighter->getOriginal('regex.pattern')) {
        $hl->setRegexPattern($highlighter->get('regex.pattern'));
      }
      if ($highlighter->get('regex.maxAnalyzedChars') != $highlighter->getOriginal('regex.maxAnalyzedChars')) {
        $hl->setRegexMaxAnalyzedChars($highlighter->get('regex.maxAnalyzedChars'));
      }
      if ($excerpt) {
        // If the field doesn't exist yet getField() will add it.
        $excerpt_field = $hl->getField('spell');
        $excerpt_field->setSnippets($highlighter->get('excerpt.snippets'));
        $excerpt_field->setFragSize($highlighter->get('excerpt.fragsize'));
        $excerpt_field->setMergeContiguous($highlighter->get('excerpt.mergeContiguous'));
      }
      if ($highlight && !empty($highlighted_fields)) {
        foreach ($highlighted_fields as $highlighted_field) {
          // We must not set the fields at once using setFields() to not break
          // the excerpt feature above.
          $hl->addField($highlighted_field);
        }
        // @todo the amount of snippets need to be increased to get highlighting
        //   of multi value fields to work.
        // @see https://drupal.org/node/2753635
        $hl->setSnippets(1);
        $hl->setFragSize(0);
        $hl->setMergeContiguous($highlighter->get('highlight.mergeContiguous'));
        $hl->setRequireFieldMatch($highlighter->get('highlight.requireFieldMatch'));
      }
    }
  }

  /**
   * Changes the query to a "More Like This" query.
   *
   * @param \Solarium\QueryType\MorelikeThis\Query $solarium_query
   *   The solr mlt query.
   * @param string $index_id
   *   Solr specific index ID.
   * @param array $index_fields
   *   The fields in the index to add mlt for.
   * @param array $fields
   *   The fields to add mlt for.
   *
   * @return \Solarium\QueryType\MorelikeThis\Query $solarium_query
   */
  protected function getMoreLikeThisQuery(QueryInterface $query, $index_id, $index_fields = [], $fields = []) {
    $connector = $this->getSolrConnector();
    $solarium_query = $connector->getMoreLikeThisQuery();
    $query_helper = $connector->getQueryHelper($solarium_query);
    $mlt_options = $query->getOption('search_api_mlt');
    $language_ids = $query->getLanguages();
    if (empty($language_ids)) {
      // If the query isn't already restricted by languages we have to do it
      // here in order to limit the MLT suggestions to be of the same language
      // as the currently shown one.
      $language_ids[] = \Drupal::languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
      $query->setLanguages($language_ids);
    }

    $ids = [];
    foreach ($query->getIndex()->getDatasources() as $datasource) {
      if ($entity_type_id = $datasource->getEntityTypeId()) {
        $entity = \Drupal::entityTypeManager()
          ->getStorage($entity_type_id)
          ->load($mlt_options['id']);

        if ($entity instanceof ContentEntityInterface) {
          $translated = FALSE;
          if ($entity->isTranslatable()) {
            foreach ($language_ids as $language_id) {
              if ($entity->hasTranslation($language_id)) {
                $ids[] = SearchApiUtility::createCombinedId(
                  $datasource->getPluginId(),
                  $datasource->getItemId(
                    $entity->getTranslation($language_id)->getTypedData()
                  )
                );
                $translated = TRUE;
              }
            }
          }

          if (!$translated) {
            // Fall back to the default language of the entity.
            $ids[] = SearchApiUtility::createCombinedId(
              $datasource->getPluginId(),
              $datasource->getItemId($entity->getTypedData())
            );
          }
        }
        else {
          $ids[] = $mlt_options['id'];
        }
      }
    }

    if (!empty($ids)) {
      array_walk($ids, function (&$id, $key) use ($index_id, $query_helper) {
        $id = $this->createId($index_id, $id);
        $id = $query_helper->escapePhrase($id);
      });

      $solarium_query->setQuery('id:' . implode(' id:', $ids));
    }

    $mlt_fl = [];
    foreach ($mlt_options['fields'] as $mlt_field) {
      // Solr 4 has a bug which results in numeric fields not being supported
      // in MLT queries. Date fields don't seem to be supported at all.
      $version = $this->getSolrConnector()->getSolrVersion();
      if ($fields[$mlt_field][0] === 'd' || (version_compare($version, '4', '==') && in_array($fields[$mlt_field][0], array('i', 'f')))) {
        continue;
      }

      $mlt_fl[] = $fields[$mlt_field];
      // For non-text fields, set minimum word length to 0.
      if (isset($index_fields[$mlt_field]) && !$this->dataTypeHelper->isTextType($index_fields[$mlt_field]->getType())) {
        $solarium_query->addParam('f.' . $fields[$mlt_field] . '.mlt.minwl', 0);
      }
    }

    $solarium_query->setMltFields($mlt_fl);
    // @todo Add some configuration options here and support more MLT options.
    $solarium_query->setMinimumDocumentFrequency(1);
    $solarium_query->setMinimumTermFrequency(1);

    return $solarium_query;
  }

  /**
   * Adds spatial features to the search query.
   *
   * @param \Solarium\QueryType\Select\Query\Query $solarium_query
   *   The solr query.
   * @param array $spatial_options
   *   The spatial options to add.
   * @param array $field_names
   *   The field names, to add the spatial options for.
   *
   * @throws \Drupal\search_api_solr\SearchApiSolrException
   */
  protected function setSpatial(Query $solarium_query, array $spatial_options, $field_names = []) {
    if (count($spatial_options) > 1) {
      throw new SearchApiSolrException('Only one spatial search can be handled per query.');
    }

    $spatial = reset($spatial_options);
    $solr_field = $field_names[$spatial['field']];
    $distance_field = $spatial['field'] . '__distance';
    $solr_distance_field = $field_names[$distance_field];
    $spatial['lat'] = (float) $spatial['lat'];
    $spatial['lon'] = (float) $spatial['lon'];
    $spatial['radius'] = isset($spatial['radius']) ? (float) $spatial['radius'] : 0.0;
    $spatial['min_radius'] = isset($spatial['min_radius']) ? (float) $spatial['min_radius'] : 0.0;

    if (!isset($spatial['filter_query_conditions'])) {
      $spatial['filter_query_conditions'] = [];
    }
    $spatial['filter_query_conditions'] += [
      'field' => $solr_field,
      'value' => $spatial['radius'],
      'operator' => '<',
    ];

    // Add a field to the result set containing the calculated distance.
    $solarium_query->addField($solr_distance_field . ':geodist()');
    // Set the common spatial parameters on the query.
    $spatial_query = $solarium_query->getSpatial();
    $spatial_query->setDistance($spatial['radius']);
    $spatial_query->setField($solr_field);
    $spatial_query->setPoint($spatial['lat'] . ',' . $spatial['lon']);
    // Add the conditions of the spatial query. This might adust the values of
    // 'radius' and 'min_radius' required later for facets.
    $solarium_query->createFilterQuery($solr_field)
      ->setQuery($this->createLocationFilterQuery($spatial));

    // Tell solr to sort by distance if the field is given by Search API.
    $sorts = $solarium_query->getSorts();
    if (isset($sorts[$solr_distance_field])) {
      $new_sorts = [];
      foreach ($sorts as $key => $order) {
        if ($key == $solr_distance_field) {
          $new_sorts['geodist()'] = $order;
        }
        else {
          $new_sorts[$key] = $order;
        }
      }
      $solarium_query->clearSorts();
      $solarium_query->setSorts($new_sorts);
    }

    // Change the facet parameters for spatial fields to return distance
    // facets.
    $facet_set = $solarium_query->getFacetSet();
    if (!empty($facet_set)) {
      /** @var \Solarium\QueryType\Select\Query\Component\Facet\Field[] $facets */
      $facets = $facet_set->getFacets();
      foreach ($facets as $delta => $facet) {
        $facet_options = $facet->getOptions();
        if ($facet_options['field'] != $solr_distance_field) {
          continue;
        }
        $facet_set->removeFacet($delta);

        $limit = $facet->getLimit();

        // @todo Check if these defaults make any sense.
        $steps = $limit > 0 ? $limit : 5;
        $step = ($spatial['radius'] - $spatial['min_radius']) / $steps;

        for ($i = 0; $i < $steps; $i++) {
          $distance_min = $spatial['min_radius'] + ($step * $i);
          // @todo $step - 1 means 1km less. That opens a gap in the facets of
          //   1km that is not covered.
          $distance_max = $distance_min + $step - 1;
          // Define our own facet key to transport the min and max values.
          // These will be extracted in extractFacets().
          $key = "spatial-{$distance_field}-{$distance_min}-{$distance_max}";
          // Due to a limitation/bug in Solarium, it is not possible to use
          // setQuery method for geo facets.
          // So the key is misused to get a correct query.
          // @see https://github.com/solariumphp/solarium/issues/229
          $facet_set->createFacetQuery($key . ' frange l=' . $distance_min . ' u=' . $distance_max)->setQuery('geodist()');
        }
      }
    }
  }

  /**
   * Adds rpt spatial features to the search query.
   *
   * @param \Solarium\QueryType\Select\Query\Query $solarium_query
   *   The solr query.
   * @param array $rpt_options
   *   The rpt spatial options to add.
   * @param array $field_names
   *   The field names, to add the rpt spatial options for.
   *
   * @throws \Drupal\search_api_solr\SearchApiSolrException
   *   Thrown when more than one rpt spatial searches are added.
   */
  protected function setRpt(Query $solarium_query, array $rpt_options, $field_names = array()) {
    // Add location filter
    if (count($rpt_options) > 1) {
      throw new SearchApiSolrException('Only one spatial search can be handled per query.');
    }

    $rpt = reset($rpt_options);
    $solr_field = $field_names[$rpt['field']];
    $rpt['geom'] = isset($rpt['geom']) ? $rpt['geom'] : '["-180 -90" TO "180 90"]';

    // Add location filter
    $solarium_query->createFilterQuery($solr_field)->setQuery($solr_field . ':' . $rpt['geom']);

    // Add Solr Query params
    $solarium_query->addParam('facet', 'on');
    $solarium_query->addParam('facet.heatmap', $solr_field);
    $solarium_query->addParam('facet.heatmap.geom', $rpt['geom']);
    $solarium_query->addParam('facet.heatmap.format', $rpt['format']);
    $solarium_query->addParam('facet.heatmap.maxCells', $rpt['maxCells']);
    $solarium_query->addParam('facet.heatmap.gridLevel', $rpt['gridLevel']);
  }

  /**
   * Sets sorting for the query.
   */
  protected function setSorts(Query $solarium_query, QueryInterface $query, $field_names = []) {
    $new_schema_version = version_compare($this->getSolrConnector()->getSchemaVersion(), '4.4', '>=');
    foreach ($query->getSorts() as $field => $order) {
      $f = '';
      // First wee need to handle special fields which are prefixed by
      // 'search_api_'. Otherwise they will erroneously be treated as dynamic
      // string fields by the next detection below because they start with an
      // 's'. This way we for example ensure that search_api_relevance isn't
      // modified at all.
      if (strpos($field, 'search_api_') === 0) {
        if ($field == 'search_api_random') {
          // The default Solr schema provides a virtual field named "random_*"
          // that can be used to randomly sort the results; the field is
          // available only at query-time. See schema.xml for more details about
          // how the "seed" works.
          $params = $query->getOption('search_api_random_sort', []);
          // Random seed: getting the value from parameters or computing a new
          // one.
          $seed = !empty($params['seed']) ? $params['seed'] : mt_rand();
          $f = $field_names[$field] . '_' . $seed;
        }
      }
      elseif ($new_schema_version) {
        // @todo Both detections are redundant to some parts of
        //   SearchApiSolrBackend::getDocuments(). They should be combined in a
        //   single place to avoid errors in the future.
        if (strpos($field_names[$field], 't') === 0 || strpos($field_names[$field], 's') === 0) {
          // For fulltext fields use the dedicated sort field for faster alpha
          // sorts. Use the same field for strings to sort on a normalized
          // value.
          $f = 'sort_' . $field;
        }
        elseif (preg_match('/^([a-z]+)m(_.*)/', $field_names[$field], $matches)) {
          // For other multi-valued fields (which aren't sortable by nature) we
          // use the same hackish workaround like the DB backend: just copy the
          // first value in a single value field for sorting.
          $f = $matches[1] . 's' . $matches[2];
        }
      }

      if (!$f) {
        $f = $field_names[$field];
      }

      $solarium_query->addSort($f, strtolower($order));
    }
  }

  /**
   * Sets grouping for the query.
   *
   * @todo This code is outdated and needs to be reviewd and refactored.
   */
  protected function setGrouping(Query $solarium_query, QueryInterface $query, $grouping_options = array(), $index_fields = array(), $field_names = array()) {
    $group_params['group'] = 'true';
    // We always want the number of groups returned so that we get pagers done
    // right.
    $group_params['group.ngroups'] = 'true';
    if (!empty($grouping_options['truncate'])) {
      $group_params['group.truncate'] = 'true';
    }
    if (!empty($grouping_options['group_facet'])) {
      $group_params['group.facet'] = 'true';
    }
    foreach ($grouping_options['fields'] as $collapse_field) {
      $type = $index_fields[$collapse_field]['type'];
      // Only single-valued fields are supported.
      if ($this->dataTypeHelper->isTextType($type)) {
        $warnings[] = $this->t('Grouping is not supported for field @field. Only single-valued fields not indexed as "Fulltext" are supported.',
          array('@field' => $index_fields[$collapse_field]['name']));
        continue;
      }
      $group_params['group.field'][] = $field_names[$collapse_field];
    }
    if (empty($group_params['group.field'])) {
      unset($group_params);
    }
    else {
      if (!empty($grouping_options['group_sort'])) {
        foreach ($grouping_options['group_sort'] as $group_sort_field => $order) {
          if (isset($fields[$group_sort_field])) {
            $f = $fields[$group_sort_field];
            if (substr($f, 0, 3) == 'ss_') {
              $f = 'sort_' . substr($f, 3);
            }
            $order = strtolower($order);
            $group_params['group.sort'][] = $f . ' ' . $order;
          }
        }
        if (!empty($group_params['group.sort'])) {
          $group_params['group.sort'] = implode(', ', $group_params['group.sort']);
        }
      }
      if (!empty($grouping_options['group_limit']) && ($grouping_options['group_limit'] != 1)) {
        $group_params['group.limit'] = $grouping_options['group_limit'];
      }
    }
    foreach ($group_params as $param_id => $param_value) {
      $solarium_query->addParam($param_id, $param_value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractContentFromFile($filepath) {
    $connector = $this->getSolrConnector();

    $query = $connector->getExtractQuery();
    $query->setExtractOnly(TRUE);
    $query->setFile($filepath);

    // Execute the query.
    $result = $connector->extract($query);
    return $connector->getContentFromExtractResult($result, $filepath);
  }

  /**
   * {@inheritdoc}
   */
  public function getBackendDefinedFields(IndexInterface $index) {
    $location_distance_fields = [];

    foreach ($index->getFields() as $field) {
      if ($field->getType() == 'location') {
        $distance_field_name = $field->getFieldIdentifier() . '__distance';
        $property_path_name = $field->getPropertyPath() . '__distance';
        $distance_field = new Field($index, $distance_field_name);
        $distance_field->setLabel($field->getLabel() . ' (distance)');
        $distance_field->setDataDefinition(DataDefinition::create('decimal'));
        $distance_field->setType('decimal');
        $distance_field->setDatasourceId($field->getDatasourceId());
        $distance_field->setPropertyPath($property_path_name);

        $location_distance_fields[$distance_field_name] = $distance_field;
      }
    }

    return $location_distance_fields;
  }

}

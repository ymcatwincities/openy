<?php

/**
 * @file
 * Contains \Drupal\config_inspector\Controller\ConfigInspectorController.
 */

namespace Drupal\config_inspector\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\config_inspector\ConfigInspectorManager;
use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller for the config_inspector module.
 */
class ConfigInspectorController extends ControllerBase {

  /**
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The configuration inspector manager.
   *
   * @var \Drupal\config_inspector\ConfigInspectorManager
   */
  protected $configInspectorManager;

  /**
   * The string translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(StorageInterface $storage, ConfigInspectorManager $config_inspector_manager, TranslationManager $translation_manager) {
    $this->storage = $storage;
    $this->configInspectorManager = $config_inspector_manager;
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('plugin.manager.config_inspector'),
      $container->get('string_translation')
    );
  }

  /**
   * Builds a page listing all configuration keys to inspect.
   *
   * @return array
   *   A render array representing the list.
   */
  public function overview() {
    $page['#title'] = $this->t('Inspect');
    $page['#attached']['library'][] = 'system/drupal.system.modules';

    $page['filters'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('table-filter', 'js-show'),
      ),
    );

    $page['filters']['text'] = array(
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $this->t('Search for a configuration key'),
      '#attributes' => array(
        'class' => array('table-filter-text'),
        'data-table' => '.config-inspector-list',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the configuration key to filter by.'),
      ),
    );

    $page['table'] = array(
      '#type' => 'table',
      '#header' => array(
        'name' => t('Configuration key'),
        'schema' => t('Schema'),
        'list' => t('List'),
        'tree' => t('Tree'),
        'form' => t('Form'),
        'raw' => t('Raw data'),
      ),
      '#attributes' => array(
        'class' => array(
          'config-inspector-list',
        ),
      ),
    );

    foreach ($this->storage->listAll() as $name) {
      $label = '<span class="table-filter-text-source">' . $name . '</span>';
      // Elements without a schema are displayed to help debugging.
      if (!$this->configInspectorManager->hasSchema($name)) {
        $page['table'][] = array(
          'name' => array('#markup' => $label),
          'schema' => array('#markup' => t('None')),
          'list' => array('#markup' => t('N/A')),
          'tree' => array('#markup' => t('N/A')),
          'form' => array('#markup' => t('N/A')),
          'raw'  => array('#markup' => $this->l($this->t('Raw data'), new Url('config_inspector.raw_page', array('name' => $name)))),
        );
      }
      else {
        $schema = t('Correct');
        $result = $this->configInspectorManager->checkValues($name);
        if (is_array($result)) {
          // The no-schema case is covered above already, if we got errors, the
          // schema is partial.
          $schema = $this->translationManager->formatPlural(count($result), '@count error', '@count errors');
        }
        $page['table'][] = array(
          'name' => array('#markup' => $label),
          'schema' => array('#markup' => $schema),
          'list' => array('#markup' => $this->l($this->t('List'), new Url('config_inspector.list_page', array('name' => $name)))),
          'tree' => array('#markup' => $this->l($this->t('Tree'), new Url('config_inspector.tree_page', array('name' => $name)))),
          'form' => array('#markup' => $this->l($this->t('Form'), new Url('config_inspector.form_page', array('name' => $name)))),
          'raw' => array('#markup' => $this->l($this->t('Raw data'), new Url('config_inspector.raw_page', array('name' => $name)))),
        );
      }
    }
    return $page;
  }

  /**
   * List (table) inspection view of the configuration.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return array
   *   A render array for a list view.
   */
  public function getList($name) {
    $config_schema = $this->configInspectorManager->getConfigSchema($name);
    $output = $this->formatList($name, $config_schema);
    $output['#title'] = $this->t('List of configuration data for %name', array('%name' => $name));
    return $output;
  }

  /**
   * Tree inspection view of the configuration.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return array
   *   A render array for a tree view.
   */
  public function getTree($name) {
    $config_schema = $this->configInspectorManager->getConfigSchema($name);
    $output = $this->formatTree($config_schema);
    $output['#title'] = $this->t('Tree of configuration data for %name', array('%name' => $name));
    return $output;
  }

  /**
   * Form based configuration data inspection.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return array
   *   A render array for a form view.
   */
  public function getForm($name) {
    $config_schema = $this->configInspectorManager->getConfigSchema($name);
    $output = \Drupal::formBuilder()->getForm('\Drupal\config_inspector\Form\ConfigInspectorItemForm', $config_schema);
    $output['#title'] = $this->t('Raw configuration data for %name', array('%name' => $name));
    return $output;
  }

  /**
   * Raw configuration data inspection.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return array
   *   A render array for a raw dump view.
   */
  public function getRawData($name) {
    $data = $this->configInspectorManager->getConfigData($name);
    $output = array(
      '#title' => $this->t('Raw configuration data for %name', array('%name' => $name)),
      'config' => $this->formatData($data, 'Configuration data'),
      'schema' => $this->formatData(NULL, 'Configuration schema'),
      'validation' => $this->formatData(TRUE, 'Configuration validation'),
    );

    if ($this->configInspectorManager->hasSchema($name)) {
      $definition = $this->configInspectorManager->getDefinition($name);
      $output['schema'] = $this->formatData($definition, 'Configuration schema');

      $result = $this->configInspectorManager->checkValues($name);
      if (is_array($result)) {
        $output['validation'] = $this->formatData($result, 'Configuration validation');
      }
    }

    return $output;
  }

  /**
   * Format config schema as list table.
   */
  protected function formatList($config_name, $config_schema) {
    $rows = array();
    $errors = (array) $this->configInspectorManager->checkValues($config_name);
    $schema = $this->configInspectorManager->convertConfigElementToList($config_schema);
    foreach ($schema as $key => $element) {
      $definition = $element->getDataDefinition();

      $rows[] = array(
        'class' => isset($errors[$config_name . ':' . $key]) ? array('config-inspector-error') : array(),
        'data' => array(
          array('class' => array('icon'), 'data' => ''),
          $key,
          $definition['label'],
          $definition['type'],
          $this->formatValue($element),
          @$errors[$config_name . ':' . $key] ?: '',
        ),
      );
    }
    return array(
      '#attached' => array('library' => array('config_inspector/config_inspector')),
      '#type' => 'table',
      '#header' => array(
        '',
        t('Name'),
        t('Label'),
        t('Type'),
        t('Value'),
        t('Error'),
      ),
      '#rows' => $rows,
    );
  }

  /**
   * Format config schema as a tree.
   */
  public function formatTree($schema, $collapsed = FALSE, $base_key = '') {
    $build = array();
    foreach ($schema as $key => $element) {
      $definition = $element->getDataDefinition();
      $label = $definition['label'] ?: t('N/A');
      $type = $definition['type'];
      $element_key = $base_key . $key;
      if ($element instanceof ArrayElement) {
        $build[$key] = array(
          '#type' => 'details',
          '#title' => $label,
          '#description' => $element_key . ' (' . $type . ')',
          '#description_display' => 'after',
          '#collapsible' => TRUE,
          '#collapsed' => $collapsed,
        ) + $this->formatTree($element, TRUE, $element_key . '.');
      }
      else {
        $build[$key] = array(
          '#type' => 'item',
          '#title' => $label,
          '#markup' => SafeMarkup::checkPlain($this->formatValue($element)),
          '#description' => $element_key . ' (' . $type . ')',
          '#description_display' => 'after',
        );
      }
    }
    return $build;
  }

  /**
   * Formats a value as a string, for readable output.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $element
   *   The value element.
   *
   * @return string
   *   The value in string form.
   */
  protected function formatValue(TypedDataInterface $element) {
    $value = $element->getValue();
    if (is_scalar($value)) {
      return SafeMarkup::checkPlain($value);
    }
    if (empty($value)) {
      return '<' . $this->t('empty') . '>';
    }
    return '<' . gettype($value) . '>';
  }

  /**
   * Helper function to dump data in a reasonably reviewable fashion.
   */
  protected function formatData($data, $title = 'Data') {
    $output = '<h2>' . $title . '</h2>';
    $output .= '<pre>';
    $output .= htmlspecialchars(var_export($data, TRUE));
    $output .= '</pre>';
    $output .= '<br />';
    return array(
      '#markup' => $output,
    );
  }

}

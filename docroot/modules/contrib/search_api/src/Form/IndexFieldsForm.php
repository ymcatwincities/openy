<?php

/**
 * @file
 * Contains \Drupal\search_api\Form\IndexFieldsForm.
 */

namespace Drupal\search_api\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\DataType\DataTypePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring the fields of a search index.
 */
class IndexFieldsForm extends EntityForm {

  /**
   * The index for which the fields are configured.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $entity;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The data type plugin manager.
   *
   * @var \Drupal\search_api\DataType\DataTypePluginManager
   */
  protected $dataTypePluginManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_index_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormID() {
    return NULL;
  }

  /**
   * Constructs an IndexFieldsForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\search_api\DataType\DataTypePluginManager $data_type_plugin_manager
   *   The data type plugin manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, DataTypePluginManager $data_type_plugin_manager) {
    $this->entityManager = $entity_manager;
    $this->dataTypePluginManager = $data_type_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');

    /** @var \Drupal\search_api\DataType\DataTypePluginManager $data_type_plugin_manager */
    $data_type_plugin_manager = $container->get('plugin.manager.search_api.data_type');

    return new static($entity_manager, $data_type_plugin_manager);
  }

  /**
   * Retrieves the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager.
   */
  protected function getEntityManager() {
    return $this->entityManager;
  }

  /**
   * Retrieves the data type plugin manager.
   *
   * @return \Drupal\search_api\DataType\DataTypePluginManager
   *   The data type plugin manager.
   */
  public function getDataTypePluginManager() {
    return $this->dataTypePluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $index = $this->entity;

    // Set an appropriate page title.
    $form['#title'] = $this->t('Manage fields for search index %label', array('%label' => $index->label()));
    $form['#tree'] = TRUE;

    $form['description']['#markup'] = $this->t('<p>The data type of a field determines how it can be used for searching and filtering. The boost is used to give additional weight to certain fields, e.g. titles or tags.</p> <p>Whether detailed field types are supported depends on the type of server this index resides on. In any case, fields of type "Fulltext" will always be fulltext-searchable.</p>');
    if ($index->hasValidServer()) {
      $form['description']['#markup'] .= '<p>' . $this->t('Check the <a href="@server-url">' . "server's</a> backend class description for details.",
          array('@server-url' => $index->getServer()->url('canonical'))) . '</p>';
    }

    if ($fields = $index->getFieldsByDatasource(NULL, FALSE)) {
      $additional = $index->getAdditionalFieldsByDatasource(NULL);
      $form['_general'] = $this->buildFields($fields, $additional);
      $form['_general']['#title'] = $this->t('General');
    }

    foreach ($index->getDatasources() as $datasource_id => $datasource) {
      $fields = $index->getFieldsByDatasource($datasource_id, FALSE);
      $additional = $index->getAdditionalFieldsByDatasource($datasource_id);
      $form[$datasource_id] = $this->buildFields($fields, $additional);
      $form[$datasource_id]['#title'] = $datasource->label();
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * Builds the form fields for a set of fields.
   *
   * @param \Drupal\search_api\Item\FieldInterface[] $fields
   *   List of fields to display.
   * @param \Drupal\search_api\Item\AdditionalFieldInterface[] $additional
   *   List of additional fields that can be added.
   *
   * @return array
   *   The build structure.
   */
  protected function buildFields(array $fields, array $additional) {
    $data_type_plugin_manager = $this->getDataTypePluginManager();
    $types = $data_type_plugin_manager->getInstancesOptions();

    $fulltext_types = array('text');
    // Add all data types with fallback "text" to fulltext types as well.
    foreach ($data_type_plugin_manager->getInstances() as $id => $type) {
      if ($type->getFallbackType() == 'text') {
        $fulltext_types[] = $id;
      }
    }

    $boost_values = array('0.1', '0.2', '0.3', '0.5', '0.8', '1.0', '2.0', '3.0', '5.0', '8.0', '13.0', '21.0');
    $boosts = array_combine($boost_values, $boost_values);

    $build = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#theme' => 'search_api_admin_fields_table',
      '#parents' => array(),
    );

    foreach ($fields as $key => $field) {
      $build['fields'][$key]['title']['#plain_text'] = $field->getLabel();
      $build['fields'][$key]['id']['#plain_text'] = $key;
      if ($field->getDescription()) {
        $build['fields'][$key]['description'] = array(
          '#type' => 'value',
          '#value' => $field->getDescription(),
        );
      }
      $build['fields'][$key]['indexed'] = array(
        '#type' => 'checkbox',
        '#default_value' => $field->isIndexed(),
        '#disabled' => $field->isIndexedLocked(),
      );
      $css_key = '#edit-fields-' . Html::getId($key);
      $build['fields'][$key]['type'] = array(
        '#type' => 'select',
        '#options' => $types,
        '#default_value' => $field->getType(),
        '#states' => array(
          'visible' => array(
            $css_key . '-indexed' => array('checked' => TRUE),
          ),
        ),
        '#disabled' => $field->isTypeLocked(),
      );
      $build['fields'][$key]['boost'] = array(
        '#type' => 'select',
        '#options' => $boosts,
        '#default_value' => sprintf('%.1f', $field->getBoost()),
        '#states' => array(
          'visible' => array(
            $css_key . '-indexed' => array('checked' => TRUE),
          ),
        ),
      );
      foreach ($fulltext_types as $type) {
        $build['fields'][$key]['boost']['#states']['visible'][$css_key . '-type'][] = array('value' => $type);
      }
      $build['fields'][$key]['#access'] = !$field->isHidden();
    }

    if ($additional) {
      // Build our options.
      $build['additional'] = array(
        '#type' => 'details',
        '#title' => $this->t('Related fields'),
        '#description' => $this->t(
            'There are entities related to entities of this type. ' .
            'You can add their fields to the list above so they can be indexed too.'
          ) . '<br />',
        '#open' => TRUE,
        '#tree' => TRUE,
      );
      foreach ($additional as $key => $additional_field) {
        // We need to loop through each option because we need to disable the
        // checkbox if it's a dependency for another option.
        $build['additional']['field'][$key] = array(
          '#type' => 'checkbox',
          '#title' => $additional_field->getLabel(),
          '#default_value' => $additional_field->isEnabled(),
          '#disabled' => $additional_field->isLocked(),
          '#access' => !$additional_field->isHidden(),
        );
      }
      $build['additional']['actions']['#type'] = 'actions';
      $build['additional']['actions']['add'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Update'),
      );
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $index = $this->entity;

    // Store the fields configuration.
    $values = $form_state->getValues();
    $fields = $values['fields'];
    foreach ($index->getFields(FALSE) as $field_id => $field) {
      if (isset($fields[$field_id])) {
        $field->setType($fields[$field_id]['type']);
        $field->setBoost($fields[$field_id]['boost']);
        $field->setIndexed((bool) $fields[$field_id]['indexed'], TRUE);
      }
    }

    // Store the additional fields configuration.
    if (isset($values['additional']['field'])) {
      $additional = $values['additional']['field'];
      foreach ($index->getAdditionalFields() as $field_id => $additional_field) {
        $additional_field->setEnabled(!empty($additional[$field_id]), TRUE);
      }
    }

    $index->save();

    drupal_set_message($this->t('The changes were successfully saved.'));
  }

}

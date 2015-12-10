<?php

/**
 * @file
 * Provides Drupal\webform\ComponentBase.
 */

namespace Drupal\webform;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class ComponentBase extends PluginBase implements ComponentInterface {

  /**
   * $support_* variables are use to determine if a particular feature.
   * This allows common form elements to be kept in the buildform of this class.
   * @todo Is there a better way to do this? In the constructor?
   */
  protected $supports_unique = FALSE;

  protected $supports_disabled = FALSE;

  protected $supports_prefix_suffix = FALSE;

  protected $supports_placeholder = FALSE;

  protected $supports_width = FALSE;

  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL) {
    $form['#node'] = $node;

    $config = $this->getConfiguration();
    $form['nid'] = array(
      '#type' => 'value',
      '#value' => $node->id(),
    );

    $form['cid'] = array(
      '#type' => 'value',
      '#value' => $config['cid'],
    );

    $form['#component'] = $this;


    // Load basic component information from query string
    $form['type'] = array(
      '#type' => 'value',
      '#value' => $config['type'],
    );
    $form['pid'] = array(
      '#type' => 'value',
      '#value' => $config['pid'],
    );
    $form['weight'] = array(
      '#type' => 'value',
      '#value' => $config['weight'],
    );

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('This is used as a descriptive label when displaying this form element.'),
      '#size' => 60,
      '#maxlength' => NULL,
      '#required' => TRUE,
      '#default_value' => $config['name'],
      '#weight' => -20,
      '#attributes' => [
        'required' => TRUE,
        'class' => ['webform-component-name'],
      ],
    ];
    $form['form_key'] = [
      '#type' => 'machine_name',
      '#title' => t('Field key'),
      '#description' => t('Enter a machine readable key for this form element. May contain only alphanumeric characters and underscores. This key will be used as the name attribute of the form element. This value has no effect on the way data is saved, but may be helpful if doing custom form processing.'),
      '#size' => 60,
      '#machine_name' => [
        'exists' => array($this, 'componentKeyExists'),
        'source' => array('name'),
      ],
      '#maxlength' => NULL,
      '#required' => TRUE,
      '#weight' => -19,
      '#attributes' => [
        'required' => TRUE,
        'class' => ['webform-component-form-key'],
      ],
    ];
    $form['validation'] = [
      '#type' => 'details',
      '#title' => t('Validation'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['webform-component-validation'],
      ],
    ];
    $form['validation']['required'] = [
      '#type' => 'checkbox',
      '#title' => t('Required'),
      '#description' => t('Check this option if the user must enter a value.'),
      '#default_value' => $config['required'],
      '#attributes' => [
        'class' => ['webform-component-required'],
      ],
    ];
    if ($this->supports_unique) {
      $form['validation']['unique'] = array(
        '#type' => 'checkbox',
        '#title' => t('Unique'),
        '#return_value' => 1,
        '#description' => t('Check that all entered values for this field are unique. The same value is not allowed to be used twice.'),
        '#weight' => 1,
        '#default_value' => $config['extra']['unique'],
        '#parents' => array('extra', 'unique'),
      );
    }

    $form['display'] = [
      '#type' => 'details',
      '#title' => t('Display'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['webform-component-display'],
      ],
    ];
    $form['display']['wrapper_css'] = [
      '#type' => 'textfield',
      '#title' => t('Wrapper CSS classes'),
      '#description' => t('Apply a class to the wrapper around both the field and its label. Separate multiple classes by spaces.'),
      '#size' => 60,
      '#maxlength' => NULL,
      '#weight' => 20,
      '#parents' => array('extra', 'wrapper_css'),
      '#attributes' => [
        'class' => ['webform-component-wrapper-css'],
      ],
    ];
    $form['display']['field_css'] = [
      '#type' => 'textfield',
      '#title' => t('Field CSS classes'),
      '#description' => t('Apply a class to the field. Separate multiple by spaces.'),
      '#size' => 60,
      '#maxlength' => NULL,
      '#weight' => 22,
      '#parents' => array('extra', 'field_css'),
      '#attributes' => [
        'class' => ['webform-component-field-css'],
      ],
    ];
    if ($this->supports_disabled) {
      $form['display']['disabled'] = array(
        '#type' => 'checkbox',
        '#title' => t('Disabled'),
        '#return_value' => 1,
        '#description' => t('Make this field non-editable. Useful for setting an unchangeable default value.'),
        '#weight' => 11,
        '#default_value' => $config['extra']['disabled'],
        '#parents' => array('extra', 'disabled'),
      );
    }
    if ($this->supports_prefix_suffix) {
      $form['display']['field_prefix'] = array(
        '#type' => 'textfield',
        '#title' => t('Prefix text placed to the left of the textfield'),
        '#default_value' => $config['extra']['field_prefix'],
        '#description' => t('Examples: $, #, -.'),
        '#size' => 20,
        '#maxlength' => 127,
        '#weight' => 2.1,
        '#parents' => array('extra', 'field_prefix'),
      );
      $form['display']['field_suffix'] = array(
        '#type' => 'textfield',
        '#title' => t('Postfix text placed to the right of the textfield'),
        '#default_value' => $config['extra']['field_suffix'],
        '#description' => t('Examples: lb, kg, %.'),
        '#size' => 20,
        '#maxlength' => 127,
        '#weight' => 2.2,
        '#parents' => array('extra', 'field_suffix'),
      );
    }
    if ($this->supports_placeholder) {
      $form['display']['placeholder'] = array(
        '#type' => 'textfield',
        '#title' => t('Placeholder'),
        '#default_value' => $config['extra']['placeholder'],
        '#description' => t('The placeholder will be shown in the field until the user starts entering a value.'),
        '#weight' => 1,
        '#parents' => array('extra', 'placeholder'),
      );
    }
    if ($this->supports_width) {
      $form['display']['width'] = array(
        '#type' => 'textfield',
        '#title' => t('Width'),
        '#default_value' => $config['extra']['width'],
        '#description' => t('Width of the textfield.') . ' ' . t('Leaving blank will use the default size.'),
        '#size' => 5,
        '#maxlength' => 10,
        '#weight' => 0,
        '#parents' => array('extra', 'width'),
      );
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save component'),
      '#validate' => ['::validateComponentEditForm'],
    ];

    return $form;
  }

  public function componentKeyExists($value, $element, FormStateInterface $form_state) {
    // @TODO Actually check whether the field key has been used on the current
    // webform.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * @return array
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * @throws \Exception
   */
  public function save() {
    $config = $this->configuration;
    // @TODO Allow components/modules to modify the component before saving.
    if (!isset($config['cid'])) {
      $config['cid'] = $this->getNextCid($config);
    }

   if (isset($config['extra'])) {
      $config['extra'] = serialize($config['extra']);
    }

    \Drupal::database()->merge('webform_component')
      ->key(['nid' => $config['nid'], 'cid' => $config['cid']])
      ->fields(array_keys($config), array_values($config))
      ->execute();

    // Reset configuration with cid.
    $this->setConfiguration($config);
  }

  /**
   * @param $config
   *
   * @return mixed
   */
  protected function getNextCid() {
    $config = $this->getConfiguration();
    $nid = $config['nid'];
    $lock = \Drupal::lock();
    if ($lock->acquire('webform_component_insert_' . $nid, 5)) {
      $next_id_query = db_select('webform_component')->condition('nid', $nid);
      $next_id_query->addExpression('MAX(cid) + 1', 'cid');
      $cid = $next_id_query->execute()->fetchField();
      if ($cid == NULL) {
        $cid = 1;
      }
      $lock->release('webform_component_insert_' . $nid);
      return $cid;
    }
    else {
      \Drupal::logger('webform')->critical('A Webform component could not be saved because a timeout occurred while trying to acquire a lock for the node. Details: <pre>@component</pre>', array('@component' => print_r($config, TRUE)));
      return NULL;
    }
  }

}

<?php

namespace Drupal\datalayer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Defines a form that configures datalayer module settings.
 */
class DatalayerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('datalayer.settings');
    $config->set('add_page_meta', $form_state->getValue('add_page_meta'))
      ->set('output_terms', $form_state->getValue('output_terms'))
      ->set('output_fields', $form_state->getValue('output_fields'))
      ->set('lib_helper', $form_state->getValue('lib_helper'))
      ->set('entity_meta', $form_state->getValue('global_entity_meta'))
      ->set('enable_ia', $form_state->getValue('enable_ia'))
      ->set('ia_depth', $form_state->getValue('ia_depth'))
      ->set('ia_category_primary', $form_state->getValue('ia_category_primary'))
      ->set('ia_category_sub', $form_state->getValue('ia_category_sub'))
      ->set('vocabs', $form_state->getValue('vocabs'))
      ->set('expose_user_details', $form_state->getValue('expose_user_details'))
      ->set('expose_user_details_roles', $form_state->getValue('expose_user_details_roles'))
      ->set('expose_user_details_fields', $form_state->getValue('expose_user_details_fields'))
      ->set('entity_title', $form_state->getValue('entity_title'))
      ->set('entity_type', $form_state->getValue('entity_type'))
      ->set('entity_bundle', $form_state->getValue('entity_bundle'))
      ->set('entity_identifier', $form_state->getValue('entity_identifier'))
      ->set('drupal_language', $form_state->getValue('drupal_language'))
      ->set('drupal_country', $form_state->getValue('drupal_country'))
      ->set('site_name', $form_state->getValue('site_name'))
      ->set('key_replacements', $this->keyReplacementsToArray($form_state->getValue('key_replacements')))
      ->save();

    if (\Drupal::moduleHandler()->moduleExists('group')) {
      $config->set('group', $form_state->getValue('group'))
        ->set('group_label', $form_state->getValue('group_label'))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function keyReplacementsToArray($replacements) {
    $storage = [];
    $labels = explode("\r\n", $replacements);
    array_filter($labels);
    foreach ($labels as $label) {
      if (strpos($label, '|') !== FALSE) {
        $tmp = explode('|', $label);
        $storage[$tmp[0]] = $tmp[1];
      }
    }
    return $storage;
  }

  /**
   * {@inheritdoc}
   */
  protected function keyReplacementsFromArray($replacements) {
    $display = '';
    if (!is_null($replacements)) {
      foreach ($replacements as $label => $replacement) {
        $display .= $label . "|" . $replacement . "\r\n";
      }
      return $display;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['datalayer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Setup vocabs.
    $vocabs = Vocabulary::loadMultiple();
    $v_options = [];
    foreach ($vocabs as $v) {
      $v_options[$v->id()] = $v->label();
    }
    $datalayer_settings = $this->config('datalayer.settings');

    // Get available meta data.
    $meta_data = _datalayer_collect_meta_properties();

    $form['global'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Global'),
    ];
    $form['global']['add_page_meta'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add entity meta data to pages'),
      '#default_value' => $datalayer_settings->get('add_page_meta'),
    ];
    $form['global']['output_terms'] = [
      '#type' => 'checkbox',
      '#states' => [
        'enabled' => [
          ':input[name="add_page_meta"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
      '#title' => $this->t('Include taxonomy terms'),
      '#default_value' => $datalayer_settings->get('output_terms'),
    ];
    $form['global']['output_fields'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Exposes a checkbox on field settings forms to expose data.'),
      '#title' => $this->t('Include enabled field values'),
      '#default_value' => $datalayer_settings->get('output_fields'),
    ];
    $form['global']['lib_helper'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include "data layer helper" library'),
      '#default_value' => $datalayer_settings->get('lib_helper'),
      '#description' => $this->t('Provides the ability to process messages passed to the dataLayer. See: <a href=":helper">data-layer-helper</a> on GitHub.', [
        ':helper' => 'https://github.com/google/data-layer-helper',
      ]),
    ];
    if (\Drupal::moduleHandler()->moduleExists('group')) {
      $form['global']['group'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Group module support'),
        '#default_value' => $datalayer_settings->get('group'),
        '#description' => $this->t('Output the group entities on pages beloging to a group.'),
      ];
    }

    $form['entity_meta'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity meta data'),
      '#description' => $this->t('The meta data details to ouput for client-side consumption. Marking none will output everything available.'),
    ];
    $form['entity_meta']['global_entity_meta'] = [
      '#type' => 'checkboxes',
      '#states' => [
        'enabled' => [
          ':input[name="add_page_meta"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
      '#title' => '',
      '#default_value' => $datalayer_settings->get('entity_meta'),
      '#options' => array_combine($meta_data, $meta_data),
    ];

    $form['ia'] = [
      '#type' => 'details',
      '#title' => $this->t('Path architecture'),
      '#description' => $this->t('Settings for output of url path components.'),
    ];

    $form['ia']['enable_ia'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable IA'),
      '#default_value' => $datalayer_settings->get('enable_ia'),
      '#description' => $this->t('Output url path components as datalayer attributes.'),
    ];

    $ia_depth = $datalayer_settings->get('ia_depth');
    $form['ia']['ia_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Depth of paths'),
      '#default_value' => isset($ia_depth) ? $ia_depth : '3',
      '#description' => $this->t('Define how many url path components get output in dataLayer.'),
    ];

    $ia_cat_primary = $datalayer_settings->get('ia_category_primary');
    $form['ia']['ia_category_primary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Depth of paths'),
      '#default_value' => isset($ia_cat_primary) ? $ia_cat_primary : 'primaryCategory',
      '#description' => $this->t('Define the key for the primary path component.'),
    ];

    $iacatSub = $datalayer_settings->get('ia_category_sub');
    $form['ia']['ia_category_sub'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Depth of paths'),
      '#default_value' => isset($iacatSub) ? $iacatSub : 'subCategory',
      '#description' => $this->t('Define the key for sub-components (this value will get appended with numerical identifier).'),
    ];

    $form['vocabs'] = [
      '#type' => 'details',
      '#title' => $this->t('Taxonomy'),
      '#description' => $this->t('The vocabularies which should be output within page meta data. Marking none will output everything available.'),
    ];
    $form['vocabs']['vocabs'] = [
      '#type' => 'checkboxes',
      '#states' => [
        'enabled' => [
          ':input[name="output_terms"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
      '#title' => '',
      '#default_value' => $datalayer_settings->get('vocabs'),
      '#options' => $v_options,
    ];

    $form['user'] = [
      '#type' => 'details',
      '#title' => $this->t('User Details'),
      '#description' => $this->t('Details about the current user can be output to the dataLayer.'),
    ];

    $form['user']['expose_user_details'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Expose user details'),
      '#default_value' => $datalayer_settings->get('expose_user_details'),
      '#description' => $this->t('Pages that should expose active user details to the dataLayer. Leaving empty will expose nothing.'),
    ];

    $user_roles = user_roles(TRUE);
    $role_options = [];
    foreach ($user_roles as $id => $role) {
      $role_options[$id] = $role->label();
    }
    $form['user']['expose_user_details_roles'] = [
      '#type' => 'checkboxes',
      '#options' => $role_options,
      '#multiple' => TRUE,
      '#title' => $this->t('Expose user roles'),
      '#default_value' => $datalayer_settings->get('expose_user_details_roles'),
      '#description' => $this->t('Roles that should expose active user details to the dataLayer. Leaving empty will expose to all roles.'),
    ];

    $form['user']['expose_user_details_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include enabled user field values'),
      '#default_value' => $datalayer_settings->get('expose_user_details_fields'),
    ];

    $form['output'] = [
      '#type' => 'details',
      '#title' => $this->t('Data layer output keys'),
      '#description' => $this->t('Define keys used in the datalayer output. Keys for field values are configurable via the field edit form.'),
    ];

    // Entity title.
    $entity_title = $datalayer_settings->get('entity_title');
    $form['output']['entity_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity title'),
      '#default_value' => isset($entity_title) ? $entity_title : 'entityTitle',
      '#description' => $this->t('Key for the title of an entity, e.g. node title, taxonomy term name, or username.'),
    ];

    // Entity type.
    $entity_type = $datalayer_settings->get('entity_type');
    $form['output']['entity_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity type'),
      '#default_value' => isset($entity_type) ? $entity_type : 'entityType',
      '#description' => $this->t('Key for the type of an entity, e.g. node, user, or taxonomy_term.'),
    ];

    // Entity bundle.
    $entity_bundle = $datalayer_settings->get('entity_bundle');
    $form['output']['entity_bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity bundle'),
      '#default_value' => isset($entity_bundle) ? $entity_bundle : 'entityBundle',
      '#description' => $this->t('Key for the bundle of an entity, e.g. page, my_things.'),
    ];

    // Entity indetifier.
    $entity_id = $datalayer_settings->get('entity_identifier');
    $form['output']['entity_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity identifier'),
      '#default_value' => isset($entity_id) ? $entity_id : 'entityIdentifier',
      '#description' => $this->t('Key for the identifier of an entity, e.g. nid, uid, or tid.'),
    ];

    // Drupal language.
    $drupal_lang = $datalayer_settings->get('drupal_language');
    $form['output']['drupal_language'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal language'),
      '#default_value' => isset($drupal_lang) ? $drupal_lang : 'drupalLanguage',
      '#description' => $this->t('Key for the language of the site.'),
    ];

    // Drupal country.
    $drupal_country = $datalayer_settings->get('drupal_country');
    $form['output']['drupal_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal country'),
      '#default_value' => isset($drupal_country) ? $drupal_country : 'drupalCountry',
      '#description' => $this->t('Key for the country of the site.'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('group')) {
      // Group label.
      $group_label = $datalayer_settings->get('group_label');
      $form['output']['group_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Group key'),
        '#default_value' => isset($group_label) ? $group_label : 'groupKey',
        '#description' => $this->t('Key for the group.'),
      ];
    }

    // Site name.
    $drupal_sitename = $datalayer_settings->get('site_name');
    $form['output']['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal site name'),
      '#default_value' => isset($drupal_sitename) ? $drupal_sitename : 'drupalSitename',
      '#description' => $this->t('Key for the site name value.'),
    ];

    // Find a replacement.
    $key_replacements = $datalayer_settings->get('key_replacements');
    $form['output']['key_replacements'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exposed field sub-key replacements'),
      '#default_value' => !empty($key_replacements) ? $this->keyReplacementsFromArray($key_replacements) : '',
      '#description' => $this->t('For exposed fields with a sub-array of field data, enter a replacement key using the format: returned_value|replacement'),
    ];

    return parent::buildForm($form, $form_state);
  }

}

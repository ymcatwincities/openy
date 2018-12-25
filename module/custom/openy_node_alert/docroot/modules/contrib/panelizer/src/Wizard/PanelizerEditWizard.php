<?php

namespace Drupal\panelizer\Wizard;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PanelizerEditWizard extends PanelizerWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'panelizer.wizard.edit';
  }

  /**
   * {@inheritdoc}
   */
  public function initValues() {
    $cached_values = parent::initValues();
    // Load data in to values to be cached and managed by the
    // wizard until the user clicks on Save or Cancel.
    $cached_values['id'] = $this->getMachineName();
    list($entity_type, $bundle, $view_mode, $display_id) = explode('__', $this->getMachineName());
    $panelizer = \Drupal::service('panelizer');
    // Load the panels display variant.
    /** @var \Drupal\panelizer\Panelizer $panelizer */
    // @todo this $display_id looks all wrong to me since it's the name and view_mode.
    $variant_plugin = $panelizer->getDefaultPanelsDisplay($display_id, $entity_type, $bundle, $view_mode);
    $cached_values['plugin'] = $variant_plugin;
    $cached_values['label'] = $cached_values['plugin']->getConfiguration()['label'];

    $display = $panelizer->getEntityViewDisplay($entity_type, $bundle, $view_mode);
    $config = $display->getThirdPartySetting('panelizer', 'displays', []);
    if (!empty($config[$display_id]['static_context'])) {
      $cached_values['contexts'] = $config[$display_id]['static_context'];
    }
    return $cached_values;
  }

  /**
   * {@inheritdoc}
   */
  protected function customizeForm(array $form, FormStateInterface $form_state) {
    // The page actions.
    $form['wizard_actions'] = [
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => [
        'class' => ['inline'],
      ]
    ];

    // The tree of wizard steps.
    $form['wizard_tree'] = [
      '#theme' => ['panelizer_wizard_tree'],
      '#wizard' => $this,
      '#cached_values' => $form_state->getTemporaryValue('wizard'),
    ];

    $form['#theme'] = 'panelizer_wizard_form';
    $form['#attached']['library'][] = 'panelizer/wizard_admin';
    $form = parent::customizeForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(FormInterface $form_object, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $operation = $this->getOperation($cached_values);

    $actions = [];

    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#validate' => [
        '::populateCachedValues',
        [$form_object, 'validateForm'],
      ],
      '#submit' => [
        [$form_object, 'submitForm'],
      ],
    ];

    $actions['update_and_save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update and save'),
      '#button_type' => 'primary',
      '#validate' => [
        '::populateCachedValues',
        [$form_object, 'validateForm'],
      ],
      '#submit' => [
        [$form_object, 'submitForm'],
      ],
    ];

    $actions['finish'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#validate' => [
        '::populateCachedValues',
        [$form_object, 'validateForm'],
      ],
      '#submit' => [
        [$form_object, 'submitForm'],
      ],
    ];

    $actions['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => [
        '::clearTempstore'
      ],
    ];

    // Add any submit or validate functions for the step and the global ones.
    foreach (['submit', 'update_and_save', 'finish'] as $button) {
      if (isset($operation['validate'])) {
        $actions[$button]['#validate'] = array_merge($actions[$button]['#validate'], $operation['validate']);
      }
      $actions[$button]['#validate'][] = '::validateForm';
      if (isset($operation['submit'])) {
        $actions[$button]['#submit'] = array_merge($actions[$button]['#submit'], $operation['submit']);
      }
      $actions[$button]['#submit'][] = '::submitForm';
    }
    $actions['update_and_save']['#submit'][] = '::finish';
    $actions['finish']['#submit'][] = '::finish';

    if ($form_state->get('ajax')) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      $ajax_parameters = $this->getNextParameters($cached_values);
      $ajax_parameters['step'] = $this->getStep($cached_values);
      $ajax_url = Url::fromRoute($this->getRouteName(), $ajax_parameters);
      $ajax_options = [
        'query' => $this->getRequest()->query->all() + [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
      ];
      $actions['submit']['#ajax'] = [
        'callback' => '::ajaxSubmit',
        'url' => $ajax_url,
        'options' => $ajax_options,
      ];
      $actions['update_and_save']['#ajax'] = [
        'callback' => '::ajaxFinish',
        'url' => $ajax_url,
        'options' => $ajax_options,
      ];
      $actions['finish']['#ajax'] = [
        'callback' => '::ajaxFinish',
        'url' => $ajax_url,
        'options' => $ajax_options,
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = array_map('strval', [
      $this->getNextOp(),
      $this->t('Update'),
      $this->t('Update and save'),
      $this->t('Save'),
    ]);

    if (in_array($form_state->getValue('op'), $operations)) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      if ($form_state->hasValue('label')) {
        $config = $cached_values['plugin']->getConfiguration();
        $config['label'] = $form_state->getValue('label');
        $cached_values['plugin']->setConfiguration($config);
      }
      if ($form_state->hasValue('id')) {
        $cached_values['id'] = $form_state->getValue('id');
      }
      if (is_null($this->machine_name) && !empty($cached_values['id'])) {
        $this->machine_name = $cached_values['id'];
      }
      $this->getTempstore()->set($this->getMachineName(), $cached_values);
      if (!$form_state->get('ajax')) {
        $form_state->setRedirect($this->getRouteName(), $this->getNextParameters($cached_values));
      }
    }
  }

  /**
   * Clears the temporary store.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function clearTempstore(array &$form, FormStateInterface $form_state) {
    $this->getTempstore()->delete($this->getMachineName());
    list($entity_type_id, $bundle, $view_mode) = explode('__', $this->getMachineName());
    $bundle_entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getBundleEntityType();
    if ($view_mode == 'default') {
      $route = "entity.entity_view_display.{$entity_type_id}.default";
      $arguments = [
        $bundle_entity_type => $bundle,
      ];
    }
    else {
      $route = "entity.entity_view_display.{$entity_type_id}.view_mode";
      $arguments = [
        $bundle_entity_type => $bundle,
        'view_mode_name' => $view_mode,
      ];
    }
    $form_state->setRedirect($route, $arguments);
  }

}

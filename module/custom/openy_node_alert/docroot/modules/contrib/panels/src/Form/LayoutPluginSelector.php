<?php

namespace Drupal\panels\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for selecting a layout plugin.
 */
class LayoutPluginSelector extends FormBase {

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $manager;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.core.layout'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * LayoutPluginSelector constructor.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $manager
   *   The layout plugin manager.
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   */
  public function __construct(LayoutPluginManagerInterface $manager, SharedTempStoreFactory $tempstore) {
    $this->manager = $manager;
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_layout_selection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');

    /* @var $variant_plugin \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant */
    $variant_plugin = $cached_values['plugin'];
    $form['layout'] = [
      '#title' => $this->t('Layout'),
      '#type' => 'select',
      '#options' => $this->manager->getLayoutOptions(),
      '#default_value' => $variant_plugin->getConfiguration()['layout'] ?: NULL,
    ];

    $wizard = $form_state->getFormObject();
    $form['update_layout'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change Layout'),
      '#access' => !empty($variant_plugin->getConfiguration()['layout']),
      '#validate' => [
        [$this, 'validateForm'],
      ],
      '#submit' => [
        [$this, 'submitForm'],
        [$wizard, 'submitForm'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /* @var $variant_plugin \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant */
    $variant_plugin = $cached_values['plugin'];
    // If we're changing the layout, the variant plugin must remain out of date
    // until the layout is fully configured and regions are remapped.
    if ($form_state->getValue('op') == $form['update_layout']['#value']) {
      $cached_values['layout_change'] = [
        'old_layout' => $variant_plugin->getConfiguration()['layout'],
        'new_layout' => $form_state->getValue('layout'),
      ];
      /** @var \Drupal\ctools\Wizard\EntityFormWizardInterface $wizard */
      $wizard = $form_state->getFormObject();
      $next_op = $wizard->getNextOp();
      $form_state->setValue('op', $next_op);
    }
    // Creating a new layout. Take the selected layout value.
    else {
      $variant_plugin->setLayout($form_state->getValue('layout'));
    }

    $cached_values['plugin'] = $variant_plugin;

    $form_state->setTemporaryValue('wizard', $cached_values);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');

    /* @var $variant_plugin \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant */
    $variant_plugin = $cached_values['plugin'];

    if ((string)$form_state->getValue('op') == $this->t('Change Layout') && $variant_plugin->getConfiguration()['layout'] == $form_state->getValue('layout')) {
      $form_state->setErrorByName('layout', $this->t('You must select a different layout if you wish to change layouts.'));
    }
    if ($form['update_layout']['#access'] && $variant_plugin->getConfiguration()['layout'] != $form_state->getValue('layout') && $form_state->getValue('op') != $form['update_layout']['#value']) {
      $form_state->setErrorByName('layout', $this->t('To select a different layout, you must click "Change Layout".'));
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\search_api\Form\IndexProcessorsForm.
 */

namespace Drupal\search_api\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api\Processor\ProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring the processors of a search index.
 */
class IndexProcessorsForm extends EntityForm {

  /**
   * The index being configured.
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
   * The datasource manager.
   *
   * @var \Drupal\search_api\Processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * Constructs an IndexProcessorsForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\search_api\Processor\ProcessorPluginManager $processor_plugin_manager
   *   The processor plugin manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, ProcessorPluginManager $processor_plugin_manager) {
    $this->entityManager = $entity_manager;
    $this->processorPluginManager = $processor_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');
    /** @var \Drupal\search_api\Processor\ProcessorPluginManager $processor_plugin_manager */
    $processor_plugin_manager = $container->get('plugin.manager.search_api.processor');
    return new static($entity_manager, $processor_plugin_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormID() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    // Retrieve lists of all processors, and the stages and weights they have.
    if (!$form_state->has('processors')) {
      $all_processors = $this->entity->getProcessors(FALSE);
      $sort_processors = function (ProcessorInterface $a, ProcessorInterface $b) {
        return strnatcasecmp($a->label(), $b->label());
      };
      uasort($all_processors, $sort_processors);
    }
    else {
      $all_processors = $form_state->get('processors');
    }

    $stages = $this->processorPluginManager->getProcessingStages();
    $processors_by_stage = array();
    foreach ($stages as $stage => $definition) {
      $processors_by_stage[$stage] = $this->entity->getProcessorsByStage($stage, FALSE);
    }

    $processor_settings = $this->entity->getOption('processors');

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'search_api/drupal.search_api.index-active-formatters';
    $form['#title'] = $this->t('Manage processors for search index %label', array('%label' => $this->entity->label()));
    $form['description']['#markup'] = '<p>' . $this->t('Configure processors which will pre- and post-process data at index and search time.') . '</p>';

    // Add the list of processors with checkboxes to enable/disable them.
    $form['status'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled'),
      '#attributes' => array('class' => array(
        'search-api-status-wrapper',
      )),
    );
    foreach ($all_processors as $processor_id => $processor) {
      $clean_css_id = Html::cleanCssIdentifier($processor_id);
      $form['status'][$processor_id] = array(
        '#type' => 'checkbox',
        '#title' => $processor->label(),
        '#default_value' => $processor->isLocked() || !empty($processor_settings[$processor_id]),
        '#description' => $processor->getDescription(),
        '#attributes' => array(
          'class' => array(
            'search-api-processor-status-' . $clean_css_id,
          ),
          'data-id' => $clean_css_id,
        ),
        '#disabled' => $processor->isLocked(),
        '#access' => !$processor->isHidden(),
      );
    }

    $form['weights'] = array(
      '#type' => 'fieldset',
      '#title' => t('Processor order'),
    );
    // Order enabled processors per stage.
    foreach ($stages as $stage => $description) {
      $form['weights'][$stage] = array (
        '#type' => 'fieldset',
        '#title' => $description['label'],
        '#attributes' => array('class' => array(
          'search-api-stage-wrapper',
          'search-api-stage-wrapper-' . Html::cleanCssIdentifier($stage),
        )),
      );
      $form['weights'][$stage]['order'] = array(
        '#type' => 'table',
      );
      $form['weights'][$stage]['order']['#tabledrag'][] = array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'search-api-processor-weight-' . Html::cleanCssIdentifier($stage),
      );
    }
    foreach ($processors_by_stage as $stage => $processors) {
      /** @var \Drupal\search_api\Processor\ProcessorInterface $processor */
      foreach ($processors as $processor_id => $processor) {
        $weight = isset($processor_settings[$processor_id]['weights'][$stage])
          ? $processor_settings[$processor_id]['weights'][$stage]
          : $processor->getDefaultWeight($stage);
        if ($processor->isHidden()) {
          $form['processors'][$processor_id]['weights'][$stage] = array(
            '#type' => 'value',
            '#value' => $weight,
          );
          continue;
        }
        $form['weights'][$stage]['order'][$processor_id]['#attributes']['class'][] = 'draggable';
        $form['weights'][$stage]['order'][$processor_id]['#attributes']['class'][] = 'search-api-processor-weight--' . Html::cleanCssIdentifier($processor_id);
        $form['weights'][$stage]['order'][$processor_id]['#weight'] = $weight;
        $form['weights'][$stage]['order'][$processor_id]['label']['#plain_text'] = $processor->label();
        $form['weights'][$stage]['order'][$processor_id]['weight'] = array(
          '#type' => 'weight',
          '#title' => $this->t('Weight for processor %title', array('%title' => $processor->label())),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#parents' => array('processors', $processor_id, 'weights', $stage),
          '#attributes' => array('class' => array(
            'search-api-processor-weight-' . Html::cleanCssIdentifier($stage),
          )),
        );
      }
    }

    // Add vertical tabs containing the settings for the processors. Tabs for
    // disabled processors are hidden with JS magic, but need to be included in
    // case the processor is enabled.
    $form['processor_settings'] = array(
      '#title' => $this->t('Processor settings'),
      '#type' => 'vertical_tabs',
    );

    foreach ($all_processors as $processor_id => $processor) {
      $processor_form_state = new SubFormState($form_state, array('processors', $processor_id, 'settings'));
      $processor_form = $processor->buildConfigurationForm($form, $processor_form_state);
      if ($processor_form) {
        $form['settings'][$processor_id] = array(
          '#type' => 'details',
          '#title' => $processor->label(),
          '#group' => 'processor_settings',
          '#parents' => array('processors', $processor_id, 'settings'),
          '#attributes' => array('class' => array(
            'search-api-processor-settings-' . Html::cleanCssIdentifier($processor_id),
          )),
        );
        $form['settings'][$processor_id] += $processor_form;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    /** @var \Drupal\search_api\Processor\ProcessorInterface[] $processors */
    $processors = $this->entity->getProcessors(FALSE);

    // Iterate over all processors that have a form and are enabled.
    foreach ($form['settings'] as $processor_id => $processor_form) {
      if (!empty($values['status'][$processor_id])) {
        $processor_form_state = new SubFormState($form_state, array('processors', $processor_id, 'settings'));
        $processors[$processor_id]->validateConfigurationForm($form['settings'][$processor_id], $processor_form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $new_settings = array();

    // Store processor settings.
    // @todo Go through all available processors, enable/disable with method on
    //   processor plugin to allow reaction.
    /** @var \Drupal\search_api\Processor\ProcessorInterface $processor */
    $processors = $this->entity->getProcessors(FALSE);
    foreach ($processors as $processor_id => $processor) {
      if (empty($values['status'][$processor_id])) {
        continue;
      }
      $new_settings[$processor_id] = array(
        'processor_id' => $processor_id,
        'weights' => array(),
        'settings' => array(),
      );
      $processor_values = $values['processors'][$processor_id];
      if (!empty($processor_values['weights'])) {
        $new_settings[$processor_id]['weights'] = $processor_values['weights'];
      }
      if (isset($form['settings'][$processor_id])) {
        $processor_form_state = new SubFormState($form_state, array('processors', $processor_id, 'settings'));
        $processor->submitConfigurationForm($form['settings'][$processor_id], $processor_form_state);
        $new_settings[$processor_id]['settings'] = $processor->getConfiguration();
      }
    }

    // Sort the processors so we won't have unnecessary changes.
    ksort($new_settings);
    if (!$this->entity->getOption('processors', array()) !== $new_settings) {
      $this->entity->setOption('processors', $new_settings);
      $this->entity->save();
      $this->entity->reindex();
      drupal_set_message($this->t('The indexing workflow was successfully edited. All content was scheduled for reindexing so the new settings can take effect.'));
    }
    else {
      drupal_set_message($this->t('No values were changed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // We don't have a "delete" action here.
    unset($actions['delete']);

    return $actions;
  }

}

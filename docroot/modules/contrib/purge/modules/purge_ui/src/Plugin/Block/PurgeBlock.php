<?php

namespace Drupal\purge_ui\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\purge_ui\Form\PurgeBlockForm;

/**
 * Let site administrators purge the current page.
 *
 * @Block(
 *   id = "purge_ui_block",
 *   admin_label = @Translation("Purge this page"),
 * )
 */
class PurgeBlock extends BlockBase implements ContainerFactoryPluginInterface {
  use ContainerAwareTrait;

  /**
   * Constructs a new PurgeBlock instance.
   *
   * @param ContainerInterface $container
   *   The dependency injection container.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Assure that purge_block_id exists so that we can render a unique form.
    $config = $this->getConfiguration();
    if ((!isset($config['purge_block_id'])) || empty($config['purge_block_id'])) {
      return [
        '#cache' => ['max-age' => 0],
        '#markup' => $this->t('Config not found, please reconfigure block!'),
      ];
    }

    // Directly instantiate the form, to inject the configuration to its
    // constructor. Normally, instantiating with getForm() would pass in the
    // parameters only to FormBase::buildForm(), which is sadly too late as we
    // need the unique form ID already in FormBase::getFormID().
    // See https://www.drupal.org/node/2188851 for more information.
    $form = PurgeBlockForm::create($this->container, $config);
    return $this->container->get('form_builder')->getForm($form);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['purge_block_id'] = [
      '#type' => 'hidden',
      '#value' => $config['purge_block_id'],
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Provide an optional description text which will be shown above the submit button.'),
      '#default_value' => $config['description'],
    ];
    $form['submission'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission'),
    ];
    $form['submission']['submit_label'] = [
      '#size' => 30,
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Provide the label of the submit button, this is what the user clicks on.'),
      '#default_value' => $config['submit_label'],
    ];
    $form['submission']['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Invalidation type'),
      '#default_value' => $config['type'],
      '#description' => $this->t('<b>Warning:</b> only select a type which is actually supported by your purgers!'),
      '#options' => [
        'url' => $this->t("The current page's <b>url</b>."),
        'path' => $this->t("The <b>path</b> of the current page."),
        'everything' => $this->t('<b>everything</b> on the entire site.'),
      ],
    ];
    $form['submission']['execution'] = [
      '#type' => 'radios',
      '#title' => $this->t('Execution'),
      '#default_value' => $config['execution'],
      '#description' => $this->t('With direct execution, the user gets immedate feedback whether the cache invalidation succeeded or failed. The drawback is that failures, will not be queued for later retries.'),
      '#options' => [
        'direct' => $this->t("Direct execution"),
        'queue' => $this->t("Through queue"),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->setConfigurationValue('description', $values['description']);
    $this->setConfigurationValue('submit_label', $values['submission']['submit_label']);
    $this->setConfigurationValue('type', $values['submission']['type']);
    $this->setConfigurationValue('execution', $values['submission']['execution']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'purge_block_id' => sha1(microtime()),
      'description' => '',
      'submit_label' => $this->t('Clear!'),
      'type' => 'url',
      'execution' => 'direct',
    ];
  }

}

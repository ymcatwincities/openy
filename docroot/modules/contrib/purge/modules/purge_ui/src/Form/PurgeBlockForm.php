<?php

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException;
use Drupal\purge\Plugin\Purge\Purger\Exception\DiagnosticsException;
use Drupal\purge\Plugin\Purge\Purger\Exception\LockException;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;

/**
 * End-user form for \Drupal\purge_ui\Plugin\Block\PurgeBlock.
 */
class PurgeBlockForm extends FormBase {

  /**
   * The form's configuration array, which determines how and what we purge.
   *
   * @var string[]
   */
  protected $config;

  /**
   * The 'purge_ui_block_processor' plugin.
   *
   * @var null|\Drupal\purge\Plugin\Purge\Processor\ProcessorInterface
   */
  protected $processor;

  /**
   * The 'purge_ui_block_queuer' plugin.
   *
   * @var null|\Drupal\purge\Plugin\Purge\Queuer\QueuerInterface
   */
  protected $queuer;

  /**
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * Constructs a PurgeBlockForm object.
   *
   * @param string[] $config
   *   The form's configuration array, which determines how and what we purge.
   * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purge_processors
   *   The purge processors service.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purge purgers service.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purge_invalidation_factory
   *   The purge invalidations factory service.
   * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purge_queue
   *   The purge queue service.
   * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purge_queuers
   *   The purge queuers service.
   *
   * @return void
   */
  public function __construct(array $config, ProcessorsServiceInterface $purge_processors, PurgersServiceInterface $purge_purgers, InvalidationsServiceInterface $purge_invalidation_factory, QueueServiceInterface $purge_queue, QueuersServiceInterface $purge_queuers) {
    if (is_null($config)) {
      throw new \LogicException('\Drupal\purge_ui\Form\PurgeBlockForm should be directly instantiated with block configuration passed in.');
    }
    $this->config = $config;
    $this->processor = $purge_processors->get('purge_ui_block_processor');
    $this->queuer = $purge_queuers->get('purge_ui_block_queuer');
    $this->purgePurgers = $purge_purgers;
    $this->purgeInvalidationFactory = $purge_invalidation_factory;
    $this->purgeQueue = $purge_queue;
  }

  /**
   * {@inheritdoc}
   *
   * @param string[] $config
   *   The form's configuration array, which determines how and what we purge.
   */
  public static function create(ContainerInterface $container, array $config = NULL) {
    return new static(
      $config,
      $container->get('purge.processors'),
      $container->get('purge.purgers'),
      $container->get('purge.invalidation.factory'),
      $container->get('purge.queue'),
      $container->get('purge.queuers')
    );
  }

  /**
   * Gather information for the invalidation objects to be queued/purged.
   *
   * @return string[]
   *   List of expressions to be queued/purged as invalidation objects.
   */
  protected function gatherInvalidationsData() {
    $request = $this->getRequest();
    $expressions = [];
    switch ($this->config['type']) {
      case 'url':
        $expressions[] = $request->getUriForPath($request->getRequestUri());
        $expressions[] = $request->getUri();
        $expressions[] = str_replace('?' . $request->getQueryString(), '', $expressions[1]);
        break;
      case 'path':
        $expressions[] = ltrim($request->getRequestUri(), '/');
        $expressions[] = explode('?', $expressions[0])[0];
        break;
      case 'everything':
        $expressions[] = NULL;
        break;
    }
    return array_unique($expressions);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_ui.purge_' . $this->config['purge_block_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->addBuildInfo('expressions', $this->gatherInvalidationsData());
    $form = [];
    if (($this->config['execution'] === 'direct') && (!$this->processor)) {
      drupal_set_message($this->t('Please contact your site administrator to enable the block processor plugin.'), 'error');
      return ['messages' => ['#type' => 'status_messages']];

    }
    if (($this->config['execution'] === 'queue') && (!$this->queuer)) {
      drupal_set_message($this->t('Please contact your site administrator to enable the block queuer plugin.'), 'error');
      return ['messages' => ['#type' => 'status_messages']];
    }
    if (!empty($this->config['description'])) {
      $form['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->config['description'],
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->config['submit_label'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getBuildInfo()['expressions'])) {
      $form_state->setErrorByName('submit', $this->t('Invalid form submission.'));
    }
    if (($this->config['execution'] === 'direct') && (!$this->processor)) {
      $form_state->setErrorByName('submit', $this->t('Please contact your site administrator to enable the block processor plugin.'));
    }
    if (($this->config['execution'] === 'queue') && (!$this->queuer)) {
      $form_state->setErrorByName('submit', $this->t('Please contact your site administrator to enable the block queuer plugin.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Instantiate the invalidation objects with the prepared expressions.
    $invalidations = [];
    foreach ($form_state->getBuildInfo()['expressions'] as $expression) {
      $invalidations[] = $this->purgeInvalidationFactory->get(
        $this->config['type'],
        $expression
      );
    }

    // Queue execution is the easiest, as it always succeeds.
    if ($this->config['execution'] === 'queue') {
      $this->purgeQueue->add($this->queuer, $invalidations);
      drupal_set_message($this->t('Please wait for the queue to be processed!'), 'status');
    }

    // Process direct execution, which may fail in various ways.
    else {
      try {
        $this->purgePurgers->invalidate($this->processor, $invalidations);

        // Prepare and issue messages for each individual invalidation object.
        foreach ($invalidations as $invalidation) {
          $object = $invalidation->getType();
          if (!is_null($invalidation->getExpression())) {
            $object = $this->t('@object with expression "@expr"',
              [
                '@object' => $invalidation->getType(),
                '@expr' => (string) $invalidation->getExpression(),
              ]
            );
          }
          if ($invalidation->getState() === InvStatesInterface::SUCCEEDED) {
            drupal_set_message($this->t('Succesfully cleared @object.', ['@object' => $object]));
          }
          elseif ($invalidation->getState() === InvStatesInterface::PROCESSING) {
            drupal_set_message($this->t('Requested to clear multistep object of type @object!', ['@object' => $object]), 'warning');
          }
          else {
            drupal_set_message($this->t('Failed to clear @object!', ['@object' => $object]), 'error');
          }
        }
      }
      catch (DiagnosticsException $e) {
        drupal_set_message($e->getMessage(), 'error');
      }
      catch (CapacityException $e) {
        drupal_set_message($e->getMessage(), 'error');
      }
      catch (LockException $e) {
        drupal_set_message($e->getMessage(), 'error');
      }
    }
  }

}

<?php

namespace Drupal\purge_ui\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;

/**
 * Configuration dashboard for configuring the cache invalidation pipeline.
 */
class DashboardController extends ControllerBase {

  /**
   * @var \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
   */
  protected $purgeProcessors;

  /**
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface
   */
  protected $purgeQueuers;

  /**
   * The current request from the request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Central listing of code-used aliases and the routes we open modals for.
   *
   * @var string[]
   */
  protected $routes = [
    'logging'           => 'purge_ui.logging_config_form',
    'purger_add'        => 'purge_ui.purger_add_form',
    'purger_detail'     => 'purge_ui.purger_detail_form',
    'purger_config'     => 'purge_ui.purger_config_form',
    'purger_configd'    => 'purge_ui.purger_config_dialog_form',
    'purger_delete'     => 'purge_ui.purger_delete_form',
    'purger_up'         => 'purge_ui.purger_move_up_form',
    'purger_down'       => 'purge_ui.purger_move_down_form',
    'processor_add'     => 'purge_ui.processor_add_form',
    'processor_detail'  => 'purge_ui.processor_detail_form',
    'processor_config'  => 'purge_ui.processor_config_form',
    'processor_configd' => 'purge_ui.processor_config_dialog_form',
    'processor_delete'  => 'purge_ui.processor_delete_form',
    'queuer_add'        => 'purge_ui.queuer_add_form',
    'queuer_detail'     => 'purge_ui.queuer_detail_form',
    'queuer_config'     => 'purge_ui.queuer_config_form',
    'queuer_configd'    => 'purge_ui.queuer_config_dialog_form',
    'queuer_delete'     => 'purge_ui.queuer_delete_form',
    'queue_detail'      => 'purge_ui.queue_detail_form',
    'queue_change'      => 'purge_ui.queue_change_form',
    'queue_browser'     => 'purge_ui.queue_browser_form',
    'queue_empty'       => 'purge_ui.queue_empty_form',
  ];

  /**
   * Constructs a DashboardController object.
   *
   * @param \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface $purge_diagnostics
   *   Diagnostics service that reports any preliminary issues regarding purge.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purge_invalidation_factory
   *   The invalidation objects factory service.
   * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purge_processors
   *   The purge processors service.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purgers service.
   * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purge_queue
   *   The purge queue service.
   * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purge_queuers
   *   The purge queuers service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request from the request stack.
   */
  public function __construct(DiagnosticsServiceInterface $purge_diagnostics, InvalidationsServiceInterface $purge_invalidation_factory, ProcessorsServiceInterface $purge_processors, PurgersServiceInterface $purge_purgers, QueueServiceInterface $purge_queue, QueuersServiceInterface $purge_queuers, Request $request) {
    $this->purgeDiagnostics = $purge_diagnostics;
    $this->purgeInvalidationFactory = $purge_invalidation_factory;
    $this->purgeProcessors = $purge_processors;
    $this->purgePurgers = $purge_purgers;
    $this->purgeQueue = $purge_queue;
    $this->purgeQueuers = $purge_queuers;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('purge.diagnostics'),
      $container->get('purge.invalidation.factory'),
      $container->get('purge.processors'),
      $container->get('purge.purgers'),
      $container->get('purge.queue'),
      $container->get('purge.queuers'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Build all dashboard sections.
   *
   * @return array
   */
  public function build() {
    $build = [
      '#theme' => ['purge_ui_dashboard'],
      '#attached' => ['library' => ['purge_ui/purge_ui.dashboard']],
    ];
    $build['info'] = [
      '#type' => 'item',
      '#markup' => $this->t('When content on your website changes, your purge setup will take care of refreshing external caching systems and CDNs.'),
    ];
    $build['logging']     = $this->buildLoggingSection();
    $build['diagnostics'] = $this->buildDiagnosticReport();
    $build['purgers']     = $this->buildPurgers();
    $build['queue']       = $this->buildQueuersQueueProcessors();
    return $build;
  }

  /**
   * Add a section devoted to log configuration.
   *
   * @return array
   */
  protected function buildLoggingSection() {
    extract($this->getRenderLocals());
    $build = $details($this->t('Logging'));
    $build['#open'] = $this->request->get('edit-logging', FALSE);
    $build['configure'] = $buttonlink(
      $this->t("Configure logging behavior"), 'logging', '90%');
    return $build;
  }

  /**
   * Add a visual report on the current state of the purge module.
   *
   * @return array
   */
  protected function buildDiagnosticReport() {
    extract($this->getRenderLocals());
    $build = $fieldset($this->t('Status'));
    $build['report'] = [
      '#theme' => 'status_report',
      '#requirements' => $this->purgeDiagnostics->getRequirementsArray(),
    ];
    return $build;
  }

  /**
   * Manage purgers and visualize the types they support.
   *
   * @return array
   */
  protected function buildPurgers() {
    extract($this->getRenderLocals());
    $build = $details($this->t('Cache Invalidation'));
    $build['#description'] = $p($this->t("Each layer of caching on top of your site is cleared by a purger. Purgers are provided by third-party modules and support one or more types of cache invalidation."));
    $build['t'] = $table(['layer' => $this->t('Caching layer'),]);
    foreach ($this->purgeInvalidationFactory->getPlugins() as $type) {
      $label = $type['label'];
      if (strlen($type['label']) > 4) {
        $label = Unicode::truncate($type['label'], 1, FALSE);
      }
      $build['t']['#header'][$type['id']] = [
        'data' => $label,
        'title' => $this->t('@type - @description', ['@type' => $type['label'], '@description' => $type['description']]),
        'class' => [in_array($type['id'], ['tag', 'path', 'url']) ? RESPONSIVE_PRIORITY_MEDIUM : RESPONSIVE_PRIORITY_LOW],
      ];
    }

    // Visualize Drupal core as part of the cache invalidation onion.
    $row_new($build['t'], '_drupal');
    foreach ($build['t']['#header'] as $type => $definition) {
      if ($type === 'layer') {
        $row_set($build['t'], '_drupal', $type, $cell_markup($b($this->t("Drupal Origin"))));
      }
      elseif ($type === 'tag') {
        $checkmark = $cell_checked($this->t('Supported'));
        $checkmark['data']['#attributes']['supports'] = 'drupal-' . $type;
        $row_set($build['t'], '_drupal', $type, $checkmark);
      }
    }

    // Iterate the purgers and add controls and checkmarks.
    $definitions = $this->purgePurgers->getPlugins();
    $types_by_purger = $this->purgePurgers->getTypesByPurger();
    $enabled = $this->purgePurgers->getPluginsEnabled();
    $rindex = 1;
    foreach ($this->purgePurgers->getLabels() as $id => $label) {
      $row_new($build['t'], $id);
      // Add checkmarks to visualize which purgers support invalidating what.
      foreach ($build['t']['#header'] as $type => $definition) {
        if (in_array($type, $types_by_purger[$id])) {
          $checkmark = $cell_checked($this->t('Supported'));
          $checkmark['data']['#attributes']['supports'] = $id . '-' . $type;
          $row_set($build['t'], $id, $type, $checkmark);
        }
      }
      // Build the operation links from which users can select common actions.
      $definition = $definitions[$enabled[$id]];
      $ops = [];
      $ops['detail'] = $button($label, ['purger_detail', 'id' => $id]);
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        $ops['configure'] = $button($this->t("Configure"), ['purger_configd', 'id' => $id]);
      }
      $ops['delete'] = $button($this->t("Delete"), ['purger_delete', 'id' => $id]);
      if (count($enabled) !== 1) {
        if ($rindex !== 1) {
          $ops['up'] = $button($this->t("Move up"), ['purger_up', 'id' => $id]);
        }
        if ($rindex !== count($enabled)) {
          $ops['down'] = $button($this->t("Move down"), ['purger_down', 'id' => $id]);
        }
      }
      // Render the operation links into the 'layer' cell.
      $row_set($build['t'], $id, 'layer', $cell_ops($ops));
      $rindex++;
      // Add another visual layer if the purger comes with a cooldown time.
      if ($sec = $this->purgePurgers->capacityTracker()->getCooldownTime($id)) {
        $row_new($build['t'], $id . '_wait');
        $row_set($build['t'], $id . '_wait', 'layer', $cell_markup($i($this->t("@sec seconds cooldown", ['@sec' => $sec]))));
      }
    }

    // Add two rows - the first is just spacing - with a button to add purgers.
    $row_new($build['t'], '_add');
    if (count($this->purgePurgers->getPluginsAvailable())) {
      $row_set($build['t'], '_add', 'layer', $cell_ops([$button($this->t("Add purger"), 'purger_add')]));
    }
    elseif (!count($this->purgePurgers->getPluginsEnabled())) {
      $row_set($build['t'], '_add', 'layer', $cell_markup($b($this->t("Please install a module to add at least one purger."))));
    }

    // Add a row that visualizes www as part of the cache invalidation onion.
    $row_new($build['t'], '_www');
    $row_set($build['t'], '_www', 'layer', $cell_markup($b($this->t("Public Endpoint"))));

    return $build;
  }

  /**
   * Manage queuers, the queue itself and processors.
   *
   * @return array
   */
  protected function buildQueuersQueueProcessors() {
    extract($this->getRenderLocals());
    $build = $details($this->t('Queue'));
    $build['#description'] = $p($this->t("The queue holds items that need refreshing, hold your mouse over the column titles for more details."));
    $build['#open'] = $this->request->get('edit-queue', FALSE) || (!count($this->purgeQueuers)) || (!count($this->purgeProcessors));
    $build['t'] = $table([
      'queuers' => [
        'data' => $this->t('Queuers'),
        'title' => $this->t('Queuers add items to the queue upon certain events, that processors process later on.'),
      ],
      'queue' => [
        'data' => $this->t('Queue'),
        'title' => $this->t("The queue holds 'invalidation items', which instruct what needs to be invalidated from external caches."),
      ],
      'processors' => [
        'data' => $this->t('Processors'),
        'title' => $this->t('Processors are responsible for emptying the queue and putting the purgers to work each time they process. Processors can work the queue constantly or at timed intervals, it is up to you to configure a policy that makes sense for the traffic nature of your website. Multiple processors will not lead to any parallel-processing or conflicts, instead it simply means the queue is checked more often.'),
      ],
    ]);

    // Build vertical columns for the queuers, queue and processors.
    $cols = [];
    $cols['queuers'] = [];
    foreach ($this->purgeQueuers as $queuer) {
      $definition = $queuer->getPluginDefinition();
      $id = $queuer->getPluginId();
      $ops = [];
      $ops['detail'] = $button($queuer->getLabel(), ['queuer_detail', 'id' => $id]);
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        $ops['configure'] = $button($this->t("Configure"), ['queuer_configd', 'id' => $id]);
      }
      $ops['delete'] = $button($this->t("Delete"), ['queuer_delete', 'id' => $id]);
      $cols['queuers'][] = $cell_ops($ops);
    }

    $ops = [];
    $ops['detail'] = $button($this->purgeQueue->getLabel(), 'queue_detail');
    $ops['browser'] = $button($this->t('Inspect'), 'queue_browser', '900');
    $ops['change'] = $button($this->t('Change engine'), 'queue_change', '900');
    $ops['empty'] = $button($this->t('Empty'), 'queue_empty');
    $cols['queue'][] = $cell_ops($ops);

    $cols['processors'] = [];
    foreach ($this->purgeProcessors as $processor) {
      $definition = $processor->getPluginDefinition();
      $id = $processor->getPluginId();
      $ops = [];
      $ops['detail'] = $button($processor->getLabel(), ['processor_detail', 'id' => $id]);
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        $ops['configure'] = $button($this->t("Configure"), ['processor_configd', 'id' => $id]);
      }
      $ops['delete'] = $button($this->t("Delete"), ['processor_delete', 'id' => $id]);
      $cols['processors'][] = $cell_ops($ops);
    }
    $col_equalize($cols);

    // Add one last row with 'Add ...' buttons.
    $col_equalize($cols, 1);
    if (count($this->purgeQueuers->getPluginsAvailable())) {
      $cols['queuers'][] = $cell_ops([$button($this->t("Add queuer"), 'queuer_add')]);
    }
    elseif (!count($this->purgeQueuers)) {
      $cols['queuers'][] = $cell_markup($b($this->t("Please install a module to add at least one queuer.")));
    }
    if (count($this->purgeProcessors->getPluginsAvailable())) {
      $cols['processors'][] = $cell_ops([$button($this->t("Add processor"), 'processor_add')]);
    }
    elseif (!count($this->purgeProcessors)) {
      $cols['processors'][] = $cell_markup($b($this->t("Please install a module to add at least one processor.")));
    }
    $col_equalize($cols);

    // Now transform the columns into table rows.
    foreach ($cols as $col => $rows) {
      foreach ($rows as $n => $row) {
        if (!$row_isset($build['t'], $n)) {
          $row_new($build['t'], $n, $cell());
        }
        $row_set($build['t'], $n, $col, $row);
      }
    }

    return $build;
  }

  /**
   * Helper for dealing with render arrays more easily.
   *
   * With complexer render arrays with many levels deep - especially in tables -
   * it becomes much easier to read and write using this collection of template
   * variables and closures that return common render array elements.
   *
   * The variables can be imported like this:
   * @code
   * extract($this->getRenderLocals());
   * @endcode
   *
   * @return array
   */
  protected function getRenderLocals() {
    $details = function ($title) {
      return [
        '#type' => 'details',
        '#title' => $title,
        '#open' => TRUE,
      ];
    };
    $fieldset = function ($title) {
      return [
        '#type' => 'fieldset',
        '#title' => $title,
        '#attributes' => [],
      ];
    };
    // Buttons and operation links.
    $url = function ($route) {
      if (is_array($route)) {
        $args = $route;
        $route = array_shift($args);
        return Url::fromRoute($this->routes[$route], $args);
      }
      else {
        return Url::fromRoute($this->routes[$route]);
      }
    };
    $button = function ($title, $route, $width = '60%') use ($url) {
      return [
        'title' => $title,
        'url' => $url($route),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => $width]),
        ],
      ];
    };
    $buttonlink = function ($title, $route, $width = '60%') use ($url) {
      return [
        '#type' => 'link',
        '#title' => $title,
        '#url' => $url($route),
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => $width]),
        ],
      ];
    };
    // Table management.
    $table = function ($header = []) {
      return [
        '#type' => 'table',
        '#responsive' => TRUE,
        '#header' => $header,
      ];
    };
    $cell = function ($cell = []) {
      return ['data' => $cell];
    };
    $cell_checked = function ($title = '') use ($cell) {
      return $cell([
        '#theme' => 'image',
        '#width' => 18,
        '#height' => 18,
        '#uri' => 'core/misc/icons/73b355/check.svg',
        '#alt' => $title,
        '#title' => $title,
      ]);
    };
    $cell_markup = function ($markup) use ($cell) {
      return $cell(['#markup' => $markup]);
    };
    $cell_spacer = $cell_markup('&nbsp;');
    $cell_ops = function ($links) use ($cell) {
      return $cell(['#type' => 'operations', '#links' => $links]);
    };
    $col_equalize = function (&$cols, $extrarows = 0) use ($cell_spacer) {
      $rowstotal = 1;
      foreach ($cols as $col => $rows) {
        if (($rowcount = count($rows)) > $rowstotal) {
          $rowstotal = $rowcount;
        }
      }
      $rowstotal = $rowstotal + $extrarows;
      foreach ($cols as $col => $rows) {
        if ($missing = $rowstotal - count($rows)) {
          for ($i = 0; $i < $missing; $i++) {
            $cols[$col][] = $cell_spacer;
          }
        }
      }
    };
    $row_isset = function ($table, $row) {
      return isset($table['#rows'][$row]);
    };
    $row_set = function (&$table, $row, $col, $value) {
      $table['#rows'][$row]['data'][$col] = $value;
    };
    $row_new = function (&$table, $row) use ($cell_spacer, $row_set) {
      foreach ($table['#header'] as $col => $definition) {
        $row_set($table, $row, $col, $cell_spacer);
      }
    };
    // Simple shorthand tag wrapping closures.
    $tag = function ($tag, $content) {
      return '<' . $tag . '>' . $content . '</' . $tag . '>';
    };
    $b = function ($content) use ($tag) {return $tag('b', $content);};
    $i = function ($content) use ($tag) {return $tag('i', $content);};
    $p = function ($content) use ($tag) {return $tag('p', $content);};
    // Return locally defined variables so extract() can easily unpack.
    return get_defined_vars();
  }

}

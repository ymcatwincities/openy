<?php

namespace Drupal\scheduler;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Url;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\scheduler\Exception\SchedulerMissingDateException;
use Drupal\scheduler\Exception\SchedulerNodeTypeNotEnabledException;
use Psr\Log\LoggerInterface;

/**
 * Defines a scheduler manager.
 */
class SchedulerManager {

  /**
   * Date formatter service object.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Scheduler Logger service object.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Module handler service object.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Entity Manager service object.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Config Factory service object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a SchedulerManager object.
   */
  public function __construct(DateFormatter $dateFormatter, LoggerInterface $logger, ModuleHandler $moduleHandler, EntityManager $entityManager, ConfigFactory $configFactory) {
    $this->dateFormatter = $dateFormatter;
    $this->logger = $logger;
    $this->moduleHandler = $moduleHandler;
    $this->entityManager = $entityManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Publish scheduled nodes.
   *
   * @return bool
   *   TRUE if any node has been published, FALSE otherwise.
   *
   * @throws \Drupal\scheduler\Exception\SchedulerMissingDateException
   * @throws \Drupal\scheduler\Exception\SchedulerNodeTypeNotEnabledException
   */
  public function publish() {
    // @TODO: \Drupal calls should be avoided in classes.
    // Replace \Drupal::service with dependency injection?
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
    $dispatcher = \Drupal::service('event_dispatcher');

    $result = FALSE;
    $action = 'publish';

    // Select all nodes of the types that are enabled for scheduled publishing
    // and where publish_on is less than or equal to the current time.
    $nids = [];
    $scheduler_enabled_types = array_keys(_scheduler_get_scheduler_enabled_node_types($action));
    if (!empty($scheduler_enabled_types)) {
      // @TODO: \Drupal calls should be avoided in classes.
      // Replace \Drupal::entityQuery with dependency injection?
      $query = \Drupal::entityQuery('node')
        ->exists('publish_on')
        ->condition('publish_on', REQUEST_TIME, '<=')
        ->condition('type', $scheduler_enabled_types, 'IN')
        ->sort('publish_on')
        ->sort('nid');
      // Disable access checks for this query.
      // @see https://www.drupal.org/node/2700209
      $query->accessCheck(FALSE);
      $nids = $query->execute();
    }

    // Allow other modules to add to the list of nodes to be published.
    $nids = array_unique(array_merge($nids, $this->nidList($action)));

    // Allow other modules to alter the list of nodes to be published.
    $this->moduleHandler->alter('scheduler_nid_list', $nids, $action);

    // In 8.x the entity translations are all associated with one node id
    // unlike 7.x where each translation was a separate node. This means that
    // the list of node ids returned above may have some translations that need
    // processing now and others that do not.
    $nodes = Node::loadMultiple($nids);
    // @TODO: Node::loadMultiple calls should be avoided in classes.
    // Replace with dependency injection?
    foreach ($nodes as $node_multilingual) {

      // The API calls could return nodes of types which are not enabled for
      // scheduled publishing, so do not process these. This check can be done
      // once, here, as the setting will be the same for all translations.
      if (!$node_multilingual->type->entity->getThirdPartySetting('scheduler', 'publish_enable', $this->setting('default_publish_enable'))) {
        throw new SchedulerNodeTypeNotEnabledException(sprintf("Node %d '%s' will not be published because node type '%s' is not enabled for scheduled publishing", $node_multilingual->id(), $node_multilingual->getTitle(), node_get_type_label($node_multilingual)));
      }

      $languages = $node_multilingual->getTranslationLanguages();
      foreach ($languages as $language) {
        // The object returned by getTranslation() behaves the same as a $node.
        $node = $node_multilingual->getTranslation($language->getId());

        // If the current translation does not have a publish on value, or it is
        // later than the date we are processing then move on to the next.
        $publish_on = $node->publish_on->value;
        if (empty($publish_on) || $publish_on > REQUEST_TIME) {
          continue;
        }

        // Check that other modules allow the action on this node.
        if (!$this->isAllowed($node, $action)) {
          continue;
        }

        // $node->set('changed', $publish_on) will fail badly if an API call has
        // removed the date. Trap this as an exception here and give a
        // meaningful message.
        // @TODO This will now never be thrown due to the empty(publish_on)
        // check above to cater for translations. Remove this exception?
        if (empty($node->publish_on->value)) {
          $field_definitions = $this->entityManager->getFieldDefinitions('node', $node->getType());
          $field = (string) $field_definitions['publish_on']->getLabel();
          throw new SchedulerMissingDateException(sprintf("Node %d '%s' will not be published because field '%s' has no value", $node->id(), $node->getTitle(), $field));
        }

        // Trigger the PRE_PUBLISH event so that modules can react before the
        // node is published.
        $event = new SchedulerEvent($node);
        $dispatcher->dispatch(SchedulerEvents::PRE_PUBLISH, $event);
        $node = $event->getNode();

        // Update timestamps.
        $node->set('changed', $publish_on);
        $old_creation_date = $node->getCreatedTime();
        if ($node->type->entity->getThirdPartySetting('scheduler', 'publish_touch', $this->setting('default_publish_touch'))) {
          $node->setCreatedTime($publish_on);
        }

        $create_publishing_revision = $node->type->entity->getThirdPartySetting('scheduler', 'publish_revision', $this->setting('default_publish_revision'));
        if ($create_publishing_revision) {
          $node->setNewRevision();
          // Use a core date format to guarantee a time is included.
          // @TODO: 't' calls should be avoided in classes.
          // Replace with dependency injection?
          $node->revision_log = t('Node published by Scheduler on @now. Previous creation date was @date.', [
            '@now' => $this->dateFormatter->format(REQUEST_TIME, 'short'),
            '@date' => $this->dateFormatter->format($old_creation_date, 'short'),
          ]);
        }
        // Unset publish_on so the node will not get rescheduled by subsequent
        // calls to $node->save().
        $node->publish_on->value = NULL;

        // Log the fact that a scheduled publication is about to take place.
        $view_link = $node->link(t('View node'));
        $nodetype_url = Url::fromRoute('entity.node_type.edit_form', ['node_type' => $node->getType()]);
        // @TODO: \Drupal calls should be avoided in classes.
        // Replace \Drupal::l with dependency injection?
        $nodetype_link = \Drupal::l(node_get_type_label($node) . ' ' . t('settings'), $nodetype_url);
        $logger_variables = [
          '@type' => node_get_type_label($node),
          '%title' => $node->getTitle(),
          'link' => $nodetype_link . ' ' . $view_link,
        ];
        $this->logger->notice('@type: scheduled publishing of %title.', $logger_variables);

        // Use the actions system to publish the node.
        $this->entityManager->getStorage('action')->load('node_publish_action')->getPlugin()->execute($node);

        // Invoke the event to tell Rules that Scheduler has published the node.
        if ($this->moduleHandler->moduleExists('scheduler_rules_integration')) {
          _scheduler_rules_integration_dispatch_cron_event($node, 'publish');
        }

        // Trigger the PUBLISH event so that modules can react after the node is
        // published.
        $event = new SchedulerEvent($node);
        $dispatcher->dispatch(SchedulerEvents::PUBLISH, $event);
        $event->getNode()->save();

        $result = TRUE;
      }
    }

    return $result;
  }

  /**
   * Unpublish scheduled nodes.
   *
   * @return bool
   *   TRUE if any node has been unpublished, FALSE otherwise.
   *
   * @throws \Drupal\scheduler\Exception\SchedulerMissingDateException
   * @throws \Drupal\scheduler\Exception\SchedulerNodeTypeNotEnabledException
   */
  public function unpublish() {
    // @TODO: \Drupal calls should be avoided in classes.
    // Replace \Drupal::service with dependency injection?
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
    $dispatcher = \Drupal::service('event_dispatcher');

    $result = FALSE;
    $action = 'unpublish';

    // Select all nodes of the types that are enabled for scheduled unpublishing
    // and where unpublish_on is less than or equal to the current time.
    $nids = [];
    $scheduler_enabled_types = array_keys(_scheduler_get_scheduler_enabled_node_types($action));
    if (!empty($scheduler_enabled_types)) {
      // @TODO: \Drupal calls should be avoided in classes.
      // Replace \Drupal::entityQuery with dependency injection?
      $query = \Drupal::entityQuery('node')
        ->exists('unpublish_on')
        ->condition('unpublish_on', REQUEST_TIME, '<=')
        ->condition('type', $scheduler_enabled_types, 'IN')
        ->sort('unpublish_on')
        ->sort('nid');
      // Disable access checks for this query.
      // @see https://www.drupal.org/node/2700209
      $query->accessCheck(FALSE);
      $nids = $query->execute();
    }

    // Allow other modules to add to the list of nodes to be unpublished.
    $nids = array_unique(array_merge($nids, $this->nidList($action)));

    // Allow other modules to alter the list of nodes to be unpublished.
    $this->moduleHandler->alter('scheduler_nid_list', $nids, $action);

    // @TODO: Node::loadMultiple calls should be avoided in classes.
    // Replace with dependency injection?
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node_multilingual) {
      // The API calls could return nodes of types which are not enabled for
      // scheduled unpublishing. Do not process these.
      if (!$node_multilingual->type->entity->getThirdPartySetting('scheduler', 'unpublish_enable', $this->setting('default_unpublish_enable'))) {
        throw new SchedulerNodeTypeNotEnabledException(sprintf("Node %d '%s' will not be unpublished because node type '%s' is not enabled for scheduled unpublishing", $node_multilingual->id(), $node_multilingual->getTitle(), node_get_type_label($node_multilingual)));
      }

      $languages = $node_multilingual->getTranslationLanguages();
      foreach ($languages as $language) {
        // The object returned by getTranslation() behaves the same as a $node.
        $node = $node_multilingual->getTranslation($language->getId());

        // If the current translation does not have an unpublish on value, or it
        // is later than the date we are processing then move on to the next.
        $unpublish_on = $node->unpublish_on->value;
        if (empty($unpublish_on) || $unpublish_on > REQUEST_TIME) {
          continue;
        }

        // Do not process the node if it still has a publish_on time which is in
        // the past, as this implies that scheduled publishing has been blocked
        // by one of the hook functions we provide, and is still being blocked
        // now that the unpublishing time has been reached.
        $publish_on = $node->publish_on->value;
        if (!empty($publish_on) && $publish_on <= REQUEST_TIME) {
          continue;
        }

        // Check that other modules allow the action on this node.
        if (!$this->isAllowed($node, $action)) {
          continue;
        }

        // $node->set('changed', $unpublish_on) will fail badly if an API call
        // has removed the date. Trap this as an exception here and give a
        // meaningful message.
        // @TODO This will now never be thrown due to the empty(unpublish_on)
        // check above to cater for translations. Remove this exception?
        if (empty($unpublish_on)) {
          $field_definitions = $this->entityManager->getFieldDefinitions('node', $node->getType());
          $field = (string) $field_definitions['unpublish_on']->getLabel();
          throw new SchedulerMissingDateException(sprintf("Node %d '%s' will not be unpublished because field '%s' has no value", $node->id(), $node->getTitle(), $field));
        }

        // Trigger the PRE_UNPUBLISH event so that modules can react before the
        // node is unpublished.
        $event = new SchedulerEvent($node);
        $dispatcher->dispatch(SchedulerEvents::PRE_UNPUBLISH, $event);
        $node = $event->getNode();

        // Update timestamps.
        $old_change_date = $node->getChangedTime();
        $node->set('changed', $unpublish_on);

        $create_unpublishing_revision = $node->type->entity->getThirdPartySetting('scheduler', 'unpublish_revision', $this->setting('default_unpublish_revision'));
        if ($create_unpublishing_revision) {
          $node->setNewRevision();
          // Use a core date format to guarantee a time is included.
          // @TODO: 't' calls should be avoided in classes.
          // Replace with dependency injection?
          $node->revision_log = t('Node unpublished by Scheduler on @now. Previous change date was @date.', [
            '@now' => $this->dateFormatter->format(REQUEST_TIME, 'short'),
            '@date' => $this->dateFormatter->format($old_change_date, 'short'),
          ]);
        }
        // Unset unpublish_on so the node will not get rescheduled by subsequent
        // calls to $node->save(). Save the value for use when calling Rules.
        $node->unpublish_on->value = NULL;

        // Log the fact that a scheduled unpublication is about to take place.
        $view_link = $node->link(t('View node'));
        $nodetype_url = Url::fromRoute('entity.node_type.edit_form', ['node_type' => $node->getType()]);
        // @TODO: \Drupal calls should be avoided in classes.
        // Replace \Drupal::l with dependency injection?
        $nodetype_link = \Drupal::l(node_get_type_label($node) . ' ' . t('settings'), $nodetype_url);
        $logger_variables = [
          '@type' => node_get_type_label($node),
          '%title' => $node->getTitle(),
          'link' => $nodetype_link . ' ' . $view_link,
        ];
        $this->logger->notice('@type: scheduled unpublishing of %title.', $logger_variables);

        // Use the actions system to publish the node.
        $this->entityManager->getStorage('action')->load('node_unpublish_action')->getPlugin()->execute($node);

        // Invoke event to tell Rules that Scheduler has unpublished this node.
        if ($this->moduleHandler->moduleExists('scheduler_rules_integration')) {
          _scheduler_rules_integration_dispatch_cron_event($node, 'unpublish');
        }

        // Trigger the UNPUBLISH event so that modules can react before the node
        // is unpublished.
        $event = new SchedulerEvent($node);
        $dispatcher->dispatch(SchedulerEvents::UNPUBLISH, $event);
        $event->getNode()->save();

        $result = TRUE;
      }
    }

    return $result;
  }

  /**
   * Checks whether a scheduled action on a node is allowed.
   *
   * This provides a way for other modules to prevent scheduled publishing or
   * unpublishing, by implementing hook_scheduler_allow_publishing() or
   * hook_scheduler_allow_unpublishing().
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node on which the action is to be performed.
   * @param string $action
   *   The action that needs to be checked. Can be 'publish' or 'unpublish'.
   *
   * @return bool
   *   TRUE if the action is allowed, FALSE if not.
   *
   * @see hook_scheduler_allow_publishing()
   * @see hook_scheduler_allow_unpublishing()
   */
  public function isAllowed(NodeInterface $node, $action) {
    // Default to TRUE.
    $result = TRUE;
    // Check that other modules allow the action.
    $hook = 'scheduler_allow_' . $action . 'ing';
    foreach ($this->moduleHandler->getImplementations($hook) as $module) {
      $function = $module . '_' . $hook;
      $result &= $function($node);
    }

    return $result;
  }

  /**
   * Gather node IDs for all nodes that need to be $action'ed.
   *
   * Modules can implement hook_scheduler_nid_list($action) and return an array
   * of node ids which will be added to the existing list.
   *
   * @param string $action
   *   The action being performed, either "publish" or "unpublish".
   *
   * @return array
   *   An array of node ids.
   */
  public function nidList($action) {
    $nids = [];

    foreach ($this->moduleHandler->getImplementations('scheduler_nid_list') as $module) {
      $function = $module . '_scheduler_nid_list';
      $nids = array_merge($nids, $function($action));
    }

    return $nids;
  }

  /**
   * Run the lightweight cron.
   *
   * The Scheduler part of the processing performed here is the same as in the
   * normal Drupal cron run. The difference is that only scheduler_cron() is
   * executed, no other modules hook_cron() functions are called.
   *
   * This function is called from the external crontab job via url
   * /scheduler/cron/{access key} or it can be run interactively from the
   * Scheduler configuration page at /admin/config/content/scheduler/cron.
   */
  public function runLightweightCron() {
    $log = $this->setting('log');
    if ($log) {
      $this->logger->notice('Lightweight cron run activated.');
    }
    scheduler_cron();
    if (ob_get_level() > 0) {
      $handlers = ob_list_handlers();
      if (isset($handlers[0]) && $handlers[0] == 'default output handler') {
        ob_clean();
      }
    }
    if ($log) {
      // @TODO: \Drupal calls should be avoided in classes.
      // Replace \Drupal::l with dependency injection?
      $this->logger->notice('Lightweight cron run completed.', ['link' => \Drupal::l(t('settings'), Url::fromRoute('scheduler.cron_form'))]);
    }
  }

  /**
   * Helper method to access the settings of this module.
   *
   * @param string $key
   *   The key of the configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The value of the configuration item requested.
   */
  protected function setting($key) {
    return $this->configFactory->get('scheduler.settings')->get($key);
  }

}

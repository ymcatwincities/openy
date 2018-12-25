<?php

namespace Drupal\search_api\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\search_api\Task\TaskManagerInterface;

/**
 * Provides an access check for the "Execute pending tasks" route.
 */
class ExecuteTasksAccessCheck implements AccessInterface {

  /**
   * The tasks manager service.
   *
   * @var \Drupal\search_api\Task\TaskManagerInterface
   */
  protected $tasksManager;

  /**
   * Creates an ExecuteTasksAccessCheck object.
   *
   * @param \Drupal\search_api\Task\TaskManagerInterface $tasksManager
   *   The tasks manager service.
   */
  public function __construct(TaskManagerInterface $tasksManager) {
    $this->tasksManager = $tasksManager;
  }

  /**
   * Checks access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    // @todo Once #2722237 is fixed, see whether this can't just use the
    //   "search_api_task_list" cache tag instead.
    if ($this->tasksManager->getTasksCount()) {
      return AccessResult::allowed()->setCacheMaxAge(0);
    }
    return AccessResult::forbidden()->setCacheMaxAge(0);
  }

}

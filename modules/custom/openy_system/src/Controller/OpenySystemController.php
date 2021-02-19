<?php

namespace Drupal\openy_system\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Controller for openy_system access routines.
 */
class OpenySystemController implements ContainerInjectionInterface  {

    /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

    /**
   * OpenySystemController constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

    /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Check user has permission.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessOpenyAdminMenuConfigPage() {
      $modules = [
        'openy_group_schedules',
        'embedded_groupexpro_schedule',
        'openy_gxp',
        'openy_pef_gxp_sync',
        'groupex_form_cache'
      ];
      foreach ($modules as $module){
        if ($this->moduleHandler->moduleExists($module)) {
          return AccessResult::allowed();
        }
      }
      return AccessResult::neutral();

  }
}

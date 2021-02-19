<?php

namespace Drupal\openy_system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Controller for openy_system access routines.
 */
class OpenySystemController extends ControllerBase implements ContainerInjectionInterface  {

    /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

    /**
   * TermsOfUseController constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
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
   * @return \Drupal\Core\Access\AccessResult
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
      return AccessResult::forbidden();

  }
}

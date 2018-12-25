<?php

namespace Drupal\panelizer\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\panelizer\PanelizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Controller for Panelizer's Panels IPE routes.
 */
class PanelizerPanelsIPEController extends ControllerBase {

  /**
   * The Panelizer service.
   *
   * @var \Drupal\panelizer\PanelizerInterface
   */
  protected $panelizer;

  /**
   * Constructs a PanelizerPanelsIPEController.
   *
   * @param \Drupal\panelizer\PanelizerInterface $panelizer
   *   The Panelizer service.
   */
  public function __construct(PanelizerInterface $panelizer) {
    $this->panelizer = $panelizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('panelizer')
    );
  }

  /**
   * Reverts an entity view mode to a particular named default.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $view_mode
   *   The view mode.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An empty response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   */
  public function revertToDefault(FieldableEntityInterface $entity, $view_mode) {
    // Get the bundle specific default display as a fallback.
    $settings = $this->panelizer->getPanelizerSettings($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
    $default = $settings['default'];
    // Check the entity for a documented default to which we should revert.
    if ($entity->hasField('panelizer') && $entity->panelizer->first()) {
      foreach ($entity->panelizer as $item) {
        if ($item->view_mode == $view_mode && !empty($item->default)) {
          $default = $item->default;
          break;
        }
      }
    }
    // If we somehow ended up not having a default, throw an exception.
    if (empty($default)) {
      throw new BadRequestHttpException("Default name to revert to must be passed!");
    }
    $this->panelizer->setPanelsDisplay($entity, $view_mode, $default);
    return new Response();
  }

  /**
   * Custom access checker for reverting an entity view mode to a named default.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $view_mode
   *   The view mode.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessRevertToDefault(FieldableEntityInterface $entity, $view_mode, AccountInterface $account) {
    return AccessResult::allowedIf($this->panelizer->hasEntityPermission('revert to default', $entity, $view_mode, $account));
  }

}
<?php

namespace Drupal\openy_moderation_wrapper;

use Drupal\workbench_moderation\StateTransitionValidation;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\page_access\DefaultService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates whether a certain state transition is allowed.
 */
class PageAccessStateTransitionValidation extends StateTransitionValidation {

  /**
   * Page Access.
   *
   * @var \Drupal\page_access\DefaultService
   */
  protected $pageAccess;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory, DefaultService $page_access, ContainerInterface $service_container) {
    $this->serviceContainer = $service_container;
    $this->pageAccess = $page_access;
    parent::__construct($entity_type_manager, $query_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getValidTransitions(ContentEntityInterface $entity, AccountInterface $user) {
    $entity_moderation_status = $this->serviceContainer->get('openy_moderation_wrapper.entity_moderation_status');
    // Fix for page access - allow user to use all transitions.
    $access = $this->pageAccess->get_user_node_access($entity, $user);
    if ($access) {
      $bundle = $this->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle());
      $current_state = $entity->moderation_state->entity;
      $current_state_id = $current_state ? $current_state->id() : $bundle->getThirdPartySetting($entity_moderation_status->active_moderation_module(), 'default_moderation_state');
      return $this->getTransitionsFrom($current_state_id);
    }

    return parent::getValidTransitions($entity, $user);
  }

}

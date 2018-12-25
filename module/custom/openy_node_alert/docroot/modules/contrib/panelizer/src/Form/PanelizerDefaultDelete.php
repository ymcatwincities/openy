<?php

namespace Drupal\panelizer\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\panelizer\PanelizerInterface;
use Drupal\panels\PanelsDisplayManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class PanelizerDefaultDelete extends ConfirmFormBase {

  /**
   * The type of entity.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The view mode.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * The panelizer default display ID.
   *
   * @var string
   */
  protected $displayId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Panelizer service.
   *
   * @var \Drupal\panelizer\PanelizerInterface
   */
  protected $panelizer;

  /**
   * The Panels display manager.
   *
   * @var \Drupal\panels\PanelsDisplayManagerInterface
   */
  protected $panelsDisplayManager;

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $invalidator;

  /**
   * PanelizerDefaultDelete constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\panelizer\PanelizerInterface $panelizer
   *   The Panelizer service.
   * @param \Drupal\panels\PanelsDisplayManagerInterface $panels_display_manager
   *   The Panels display manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PanelizerInterface $panelizer, PanelsDisplayManagerInterface $panels_display_manager, CacheTagsInvalidatorInterface $invalidator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->panelizer = $panelizer;
    $this->panelsDisplayManager = $panels_display_manager;
    $this->invalidator = $invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('panelizer'),
      $container->get('panels.display_manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return 'Are you certain you want to delete this panelizer default?.';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $bundle_entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId)->getBundleEntityType();
    if ($this->viewMode == 'default') {
      $route = "entity.entity_view_display.{$this->entityTypeId}.default";
      $arguments = [
        $bundle_entity_type => $this->bundle,
      ];
    }
    else {
      $route = "entity.entity_view_display.{$this->entityTypeId}.view_mode";
      $arguments = [
        $bundle_entity_type => $this->bundle,
        'view_mode_name' => $this->viewMode,
      ];
    }
    return new Url($route, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panelizer_default_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $machine_name = NULL) {
    list (
      $this->entityTypeId,
      $this->bundle,
      $this->viewMode,
      $this->displayId
    ) = explode('__', $machine_name);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $display = $this->panelizer->getEntityViewDisplay($this->entityTypeId, $this->bundle, $this->viewMode);
    $displays = $this->panelizer->getDefaultPanelsDisplays($this->entityTypeId, $this->bundle, $this->viewMode, $display);
    unset($displays[$this->displayId]);
    foreach ($displays as $key => $value) {
      $displays[$key] = $this->panelsDisplayManager->exportDisplay($value);
    }
    $display->setThirdPartySetting('panelizer', 'displays', $displays);
    $display->save();
    $form_state->setRedirectUrl($this->getCancelUrl());
    $tag = "panelizer_default:{$this->entityTypeId}:{$this->bundle}:{$this->viewMode}:{$this->displayId}";
    $this->invalidator->invalidateTags([$tag]);
  }

}

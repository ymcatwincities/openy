<?php

namespace Drupal\panelizer\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\panelizer\PanelizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class PanelizerDefaultSelect extends ConfirmFormBase {

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
   * The Panelizer service.
   *
   * @var \Drupal\panelizer\PanelizerInterface
   */
  protected $panelizer;

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $invalidator;

  /**
   * PanelizerDefaultSelect constructor.
   *
   * @param \Drupal\panelizer\PanelizerInterface $panelizer
   *   The Panelizer service.
   */
  public function __construct(PanelizerInterface $panelizer, CacheTagsInvalidatorInterface $invalidator) {
    $this->panelizer = $panelizer;
    $this->invalidator = $invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('panelizer'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return 'Are you certain you want to set this panelizer default as the default for this bundle?.';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $bundle_entity_type = \Drupal::entityTypeManager()->getDefinition($this->entityTypeId)->getBundleEntityType();
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
    $settings = $this->panelizer->getPanelizerSettings($this->entityTypeId, $this->bundle, $this->viewMode, $display);
    $settings['default'] = $this->displayId;
    $this->panelizer->setPanelizerSettings($this->entityTypeId, $this->bundle, $this->viewMode, $settings, $display);
    $form_state->setRedirectUrl($this->getCancelUrl());
    $tag = "panelizer_default:{$this->entityTypeId}:{$this->bundle}:{$this->viewMode}";
    $this->invalidator->invalidateTags([$tag]);
  }

}

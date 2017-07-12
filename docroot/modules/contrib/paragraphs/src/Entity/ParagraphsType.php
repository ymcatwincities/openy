<?php

namespace Drupal\paragraphs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\paragraphs\ParagraphsBehaviorCollection;
use Drupal\paragraphs\ParagraphsBehaviorInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;

/**
 * Defines the ParagraphsType entity.
 *
 * @ConfigEntityType(
 *   id = "paragraphs_type",
 *   label = @Translation("Paragraphs type"),
 *   handlers = {
 *     "list_builder" = "Drupal\paragraphs\Controller\ParagraphsTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\paragraphs\Form\ParagraphsTypeForm",
 *       "edit" = "Drupal\paragraphs\Form\ParagraphsTypeForm",
 *       "delete" = "Drupal\paragraphs\Form\ParagraphsTypeDeleteConfirm"
 *     }
 *   },
 *   config_prefix = "paragraphs_type",
 *   admin_permission = "administer paragraphs types",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "behavior_plugins",
 *   },
 *   bundle_of = "paragraph",
 *   links = {
 *     "edit-form" = "/admin/structure/paragraphs_type/{paragraphs_type}",
 *     "delete-form" = "/admin/structure/paragraphs_type/{paragraphs_type}/delete",
 *     "collection" = "/admin/structure/paragraphs_type",
 *   }
 * )
 */
class ParagraphsType extends ConfigEntityBundleBase implements ParagraphsTypeInterface, EntityWithPluginCollectionInterface {

  /**
   * The ParagraphsType ID.
   *
   * @var string
   */
  public $id;

  /**
   * The ParagraphsType label.
   *
   * @var string
   */
  public $label;

  /**
   * The paragraphs type behavior plugins configuration keyed by their id.
   *
   * @var array
   */
  public $behavior_plugins = [];

  /**
   * Holds the collection of behavior plugins that are attached to this
   * paragraphs type.
   *
   * @var \Drupal\paragraphs\ParagraphsBehaviorCollection
   */
  protected $behaviorCollection;

  /**
   * {@inheritdoc}
   */
  public function getBehaviorPlugins() {
    if (!isset($this->behaviorCollection)) {
      $this->behaviorCollection = new ParagraphsBehaviorCollection(\Drupal::service('plugin.manager.paragraphs.behavior'), $this->behavior_plugins);
    }
    return $this->behaviorCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getBehaviorPlugin($instance_id) {
    return $this->getBehaviorPlugins()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledBehaviorPlugins() {
    return $this->getBehaviorPlugins()->getEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['behavior_plugins' => $this->getBehaviorPlugins()];
  }

  /**
   * {@inheritdoc}
   */
  public function hasEnabledBehaviorPlugin($plugin_id) {
    $plugins = $this->getBehaviorPlugins();
    if ($plugins->has($plugin_id)) {
      /** @var ParagraphsBehaviorInterface $plugin */
      $plugin = $plugins->get($plugin_id);
      $config = $plugin->getConfiguration();
      return (array_key_exists('enabled', $config) && $config['enabled'] === TRUE);
    }
    return FALSE;
  }

}

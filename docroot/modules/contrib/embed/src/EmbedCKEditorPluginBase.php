<?php

/**
 * @file
 * Contains \Drupal\embed\EmbedCKEditorPluginBase.
 */

namespace Drupal\embed;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\embed\Entity\EmbedButton;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EmbedCKEditorPluginBase extends CKEditorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The embed button query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $embedButtonQuery;

  /**
   * Constructs a Drupal\entity_embed\Plugin\CKEditorPlugin\DrupalEntity object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Query\QueryInterface $embed_button_query
   *   The entity query object for embed button.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryInterface $embed_button_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->embedButtonQuery = $embed_button_query;
    if (!empty($plugin_definition['embed_type_id'])) {
      $this->embedButtonQuery->condition('type_id', $plugin_definition['embed_type_id']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.query')->get('embed_button')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $buttons = array();

    if ($ids = $this->embedButtonQuery->execute()) {
      $embed_buttons = EmbedButton::loadMultiple($ids);
      foreach ($embed_buttons as $embed_button) {
        $buttons[$embed_button->id()] = $this->getButton($embed_button);
      }
    }

    return $buttons;
  }

  protected function getButton(EmbedButtonInterface $embed_button) {
    return [
      'id' => $embed_button->id(),
      'name' => Html::escape($embed_button->label()),
      'label' => Html::escape($embed_button->label()),
      'image' => $embed_button->getIconUrl(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'embed/embed',
    );
  }

}

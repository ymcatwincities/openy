<?php

namespace Drupal\local_fonts\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Custom Font entity.
 *
 * @ConfigEntityType(
 *   id = "local_font_config_entity",
 *   label = @Translation("Custom Font"),
 *   handlers = {
 *     "list_builder" = "Drupal\local_fonts\LocalFontConfigEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\local_fonts\Form\LocalFontConfigEntityForm",
 *       "edit" = "Drupal\local_fonts\Form\LocalFontConfigEntityForm",
 *       "delete" = "Drupal\local_fonts\Form\LocalFontConfigEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\local_fonts\LocalFontConfigEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "local_font_config_entity",
 *   admin_permission = "administer font entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "font_family" = "font_family"
 *   },
 *   links = {
 *     "canonical" = "/admin/appearance/font/local_font_config_entity/{local_font_config_entity}",
 *     "add-form" = "/admin/appearance/font/local_font_config_entity/add",
 *     "edit-form" = "/admin/appearance/font/local_font_config_entity/{local_font_config_entity}/edit",
 *     "delete-form" = "/admin/appearance/font/local_font_config_entity/{local_font_config_entity}/delete",
 *     "collection" = "/admin/appearance/font/local_font_config_entity"
 *   }
 * )
 */
class LocalFontConfigEntity extends ConfigEntityBase implements LocalFontConfigEntityInterface {

  /**
   * The Custom Font ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Custom Font label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function getFontWoffData() {
    return $this->get('font_woff_data');
  }

  /**
   * {@inheritdoc}
   */
  public function setFontWoffData($data) {
    $this->set('font_woff_data', $data);
    return $this;
  }

}

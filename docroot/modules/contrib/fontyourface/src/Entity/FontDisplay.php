<?php

namespace Drupal\fontyourface\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\fontyourface\FontDisplayInterface;

/**
 * Defines the Font display entity.
 *
 * @ConfigEntityType(
 *   id = "font_display",
 *   label = @Translation("Font display"),
 *   handlers = {
 *     "list_builder" = "Drupal\fontyourface\FontDisplayListBuilder",
 *     "form" = {
 *       "add" = "Drupal\fontyourface\Form\FontDisplayForm",
 *       "edit" = "Drupal\fontyourface\Form\FontDisplayForm",
 *       "delete" = "Drupal\fontyourface\Form\FontDisplayDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\fontyourface\FontDisplayHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "font_display",
 *   admin_permission = "administer font entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "font_url" = "font_url",
 *     "style" = "style",
 *     "weight" = "weight",
 *     "fallback" = "fallback",
 *     "selectors" = "selectors",
 *     "theme" = "theme"
 *   },
 *   links = {
 *     "canonical" = "/admin/appearance/font/font_display/{font_display}",
 *     "add-form" = "/admin/appearance/font/font_display/add",
 *     "edit-form" = "/admin/appearance/font/font_display/{font_display}/edit",
 *     "delete-form" = "/admin/appearance/font/font_display/{font_display}/delete",
 *     "collection" = "/admin/appearance/font/font_display"
 *   }
 * )
 */
class FontDisplay extends ConfigEntityBase implements FontDisplayInterface {
  /**
   * The Font display ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Font display label.
   *
   * @var string
   */
  protected $label;

  /**
   * Font URL - these are unique.
   *
   * @var string
   */
  protected $font_url;

  /**
   * Font style.
   *
   * @var string
   */
  protected $style;

  /**
   * Font weight.
   *
   * @var string
   */
  protected $weight;

  /**
   * Fallback fonts when font fails to load.
   *
   * @var string
   */
  protected $fallback;

  /**
   * Selectors where font applies.
   *
   * @var string
   */
  protected $selectors;

  /**
   * Theme - where the font + selectors will be used.
   *
   * @var string
   */
  protected $theme;

  /**
   * {@inheritdoc}
   */
  public function getFont() {
    return Font::loadByUrl($this->getFontUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getFontUrl() {
    return $this->get('font_url');
  }

  /**
   * {@inheritdoc}
   */
  public function setFontUrl($font_url) {
    $this->set('font_url', $font_url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallback() {
    return $this->get('fallback');
  }

  /**
   * {@inheritdoc}
   */
  public function setFallback($fallback) {
    $this->set('fallback', $fallback);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectors() {
    return $this->get('selectors');
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectors($selectors) {
    $this->set('selectors', $selectors);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->get('theme');
  }

  /**
   * {@inheritdoc}
   */
  public function setTheme($theme) {
    $this->set('theme', $theme);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByTheme($theme) {
    return \Drupal::entityManager()->getStorage('font_display')->loadByProperties(['theme' => $theme]);
  }

}

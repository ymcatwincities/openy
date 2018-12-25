<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

/**
 * Class AmmComponent.
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
class AmmComponent {

  /**
   * Component data.
   *
   * @var array
   */
  public $data = [];

  /**
   * Get component type.
   *
   * @return string
   *   Component type.
   */
  public function type() {
    return $this->data['component_type'];
  }

  /**
   * Get component ID.
   *
   * @return int
   *   Component ID.
   */
  public function id() {
    return $this->data['site_page_component_id'];
  }

  /**
   * Get component body.
   *
   * @return string
   *   Component body.
   */
  public function body() {
    return $this->data['body'];
  }

  /**
   * Get page ID.
   *
   * @return int
   *   Page ID.
   */
  public function pageId() {
    return $this->data['site_page_id'];
  }

  /**
   * AmmComponent constructor.
   */
  public function __construct(array $component) {
    $this->data = $component;
  }

}

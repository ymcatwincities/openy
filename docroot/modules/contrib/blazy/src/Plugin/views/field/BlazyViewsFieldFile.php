<?php

namespace Drupal\blazy\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Defines a custom field that renders a preview of a file.
 *
 * @ViewsField("blazy_file")
 */
class BlazyViewsFieldFile extends BlazyViewsFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\file\Entity\File $entity */
    $entity   = $values->_entity;
    $settings = $this->mergedViewsSettings();

    $settings['delta'] = $values->index;

    $data = $this->getImageItem($entity);
    $data['settings'] = isset($data['settings']) ? array_merge($settings, $data['settings']) : $settings;

    return $this->buildPreview($data, $entity, $entity->getFilename());
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return ['multimedia' => TRUE, 'view_mode' => 'default'] + parent::getScopedFormElements();
  }

}

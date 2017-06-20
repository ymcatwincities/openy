<?php

namespace Drupal\blazy\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Defines a custom field that renders a preview of a media.
 *
 * @ViewsField("blazy_media")
 */
class BlazyViewsFieldMedia extends BlazyViewsFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\media_entity\Entity\Media $media */
    $media = $values->_entity;

    $data['settings'] = $this->mergedViewsSettings();
    $data['settings']['delta'] = $values->index;

    return $this->buildPreview($data, $media, $media->label());
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return ['multimedia' => TRUE, 'view_mode' => 'default'] + parent::getScopedFormElements();
  }

}

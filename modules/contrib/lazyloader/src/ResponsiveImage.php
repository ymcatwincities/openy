<?php

namespace Drupal\lazyloader;

class ResponsiveImage implements \Countable, \IteratorAggregate {

  /**
   * @var \stdClass[]
   */
  protected $images = [];

  /**
   * Creates a new ResponsiveImage instance.
   *
   * @param \stdClass[] $images
   *   The images.
   */
  public function __construct(array $images) {
    $this->images = $images;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->images);
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    return $this->images[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->images);
  }

  /**
   * Creates a responsive image instance from a string.
   *
   * @return $this
   */
  public static function parse($string) {
    $strings = array_map('trim', explode(',', $string));
    $images = array_map(function($string) {
      $elements = explode(' ', $string);
      $object = new \stdClass();
      $object->uri = $elements[0];
      $object->density = NULL;
      $object->width = NULL;

      unset($elements[0]);
      foreach ($elements as $element) {
        if ($element[strlen($element) -1] === 'w') {
          $object->width = substr($element, 0, -1);
        }
        if ($element[strlen($element) -1] === 'x') {
          $object->density = substr($element, 0, -1);
        }
      }

      return $object;
    }, $strings);
    return new static($images);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return implode(', ', array_map(function ($element) {
      $string_elements = [$element->uri];
      if (!empty($element->width)) {
        $string_elements[] = $element->width . 'w';
      }
      if (!empty($element->density)) {
        $string_elements[] = $element->density . 'x';
      }

      return implode(' ', $string_elements);
    }, $this->images));
  }

}

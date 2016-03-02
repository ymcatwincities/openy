<?php
/**
 * @file
 * Contains \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase.
 */

/**
 * Each meta tag will extend this base.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

abstract class MetaNameBase extends PluginBase {
  /**
   * Machine name of the meta tag plugin.
   *
   * @var string
   */
  protected $id;

  /**
   * Official metatag name.
   *
   * @var string
   */
  protected $name;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $label;

  /**
   * A longer explanation of what the field is for.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $description;

  /**
   * The category this meta tag fits in.
   *
   * @var string
   */
  protected $group;

  /**
   * True if an image URL needs to be parsed out.
   *
   * @var boolean
   */
  protected $image;

  /**
   * True if more than one is allowed.
   *
   * @var boolean
   */
  protected $multiple;

  /**
   * The value of the metatag in this instance.
   *
   * @var mixed
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Set the properties from the annotation.
    // @TODO: Should we have setProperty() methods for each of these?
    $this->id = $plugin_definition['id'];
    $this->name = $plugin_definition['name'];
    $this->label = $plugin_definition['label'];
    $this->description = $plugin_definition['description'];
    $this->group = $plugin_definition['group'];
    $this->weight = $plugin_definition['weight'];
    $this->image = $plugin_definition['image'];
    $this->multiple = $plugin_definition['multiple'];
  }

  public function id() {
    return $this->id;
  }
  public function label() {
    return $this->label;
  }
  public function description() {
    return $this->description;
  }
  public function name() {
    return $this->name;
  }
  public function group() {
    return $this->group;
  }
  public function weight() {
    return $this->weight;
  }
  public function image() {
    return $this->image;
  }
  public function multiple() {
    return $this->multiple;
  }

  /**
   * @return bool
   *   Whether this meta tag has been enabled.
   */
  public function isActive() {
    return TRUE;
  }

  /**
   * Generate a form element for this meta tag.
   */
  public function form(array $element = array()) {
    $form = array(
      '#type' => 'textfield',
      '#title' => $this->label(),
      '#default_value' => $this->value(),
      '#maxlength' => 255,
      '#required' => isset($element['#required']) ? $element['#required'] : FALSE,
      '#description' => $this->description(),
      '#element_validate' => array(array(get_class($this), 'validateTag')),
    );

    // Optional handling for items that allow multiple values.
    if (!empty($this->multiple)) {
      $form['#description'] .= ' ' . t('Multiple values may be used, separated by a comma. Note: Tokens that return multiple values will be handled automatically.');
    }

    // Optional handling for images.
    if (!empty($this->image)) {
      $form['#description'] .= ' ' . t('This will be able to extract the URL from an image field.');
    }

    return $form;
  }

  public function value() {
    return $this->value;
  }

  public function setValue($value) {
    $this->value = $value;
  }

  public function output() {
    if (empty($this->value)) {
      // If there is no value, we don't want a tag output.
      $element = '';
    }
    else {
      // Parse out the image URL, if needed.
      $value = $this->parseImageURL();

      $element = array(
        '#tag' => 'meta',
        '#attributes' => array(
          'name' => $this->name,
          'content' => $value,
        )
      );
    }

    return $element;
  }

  /**
   * Validates the metatag data.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateTag(array &$element, FormStateInterface $form_state) {
    //@TODO: If there is some common validation, put it here. Otherwise, make it abstract?
  }

  protected function parseImageURL() {
    $value = $this->value();

    // If this contains embedded image tags, extract the image URLs.
    if ($this->image()) {
      if (strip_tags($value) != $value) {
        if ($this->multiple()) {
          $values = explode(',', $value);
        }
        else {
          $values = array($value);
        }
        foreach ($values as $key => $val) {
          $matches = array();
          preg_match('/src="([^"]*)"/', $val, $matches);
          if (!empty($matches[1])) {
            $values[$key] = $matches[1];
          }
        }
        $value = implode(',', $values);
      }
    }

    return $value;
  }

}

<?php

namespace Drupal\blazy\Dejavu;

use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\blazy\BlazyManagerInterface;

/**
 * A base for blazy views integration to have re-usable methods in one place.
 *
 * @see \Drupal\mason\Plugin\views\style\MasonViews
 * @see \Drupal\gridstack\Plugin\views\style\GridStackViews
 * @see \Drupal\slick_views\Plugin\views\style\SlickViews
 */
abstract class BlazyStylePluginBase extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * Constructs a GridStackManager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyManagerInterface $blazy_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blazyManager = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('blazy.manager'));
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * Returns available fields for select options.
   */
  public function getDefinedFieldOptions($definitions = []) {
    $field_names = $this->displayHandler->getFieldLabels();
    $definition = [];

    // Formatter based fields.
    $options = [];
    foreach ($this->displayHandler->getOption('fields') as $field => $handler) {
      // This is formatter based type, not actual field type.
      if (isset($handler['type'])) {
        switch ($handler['type']) {
          // @todo recheck other reasonable image-related formatters.
          case 'blazy':
          case 'image':
          case 'media':
          case 'media_thumbnail':
          case 'intense':
          case 'responsive_image':
          case 'video_embed_field_thumbnail':
          case 'video_embed_field_colorbox':
          case 'youtube_thumbnail':
            $options['images'][$field] = $field_names[$field];
            $options['overlays'][$field] = $field_names[$field];
            $options['thumbnails'][$field] = $field_names[$field];
            break;

          case 'list_key':
            $options['layouts'][$field] = $field_names[$field];
            break;

          case 'entity_reference_label':
          case 'text':
          case 'string':
          case 'link':
            $options['links'][$field] = $field_names[$field];
            $options['titles'][$field] = $field_names[$field];
            if ($handler['type'] != 'link') {
              $options['thumb_captions'][$field] = $field_names[$field];
            }
            break;
        }

        $classes = ['list_key', 'entity_reference_label', 'text', 'string'];
        if (in_array($handler['type'], $classes)) {
          $options['classes'][$field] = $field_names[$field];
        }

        $slicks   = strpos($handler['type'], 'slick') !== FALSE;
        $overlays = [
          'entity_reference_entity_view',
          'video_embed_field_video',
          'youtube_video',
        ];
        if ($slicks || in_array($handler['type'], $overlays)) {
          $options['overlays'][$field] = $field_names[$field];
        }

        // Allows advanced formatters/video as the main image replacement.
        // They are not reasonable for thumbnails, but main images.
        // Note: Certain Responsive image has no ID at Views, possibly a bug.
        $images = [
          'colorbox',
          'photobox',
          'video_embed_field_video',
          'youtube_video',
        ];
        if (in_array($handler['type'], $images)) {
          $options['images'][$field] = $field_names[$field];
        }
      }

      // Content: title is not really a field, unless title.module installed.
      if (isset($handler['field'])) {
        if ($handler['field'] == 'title') {
          $options['classes'][$field] = $field_names[$field];
          $options['titles'][$field] = $field_names[$field];
          $options['thumb_captions'][$field] = $field_names[$field];
        }

        if ($handler['field'] == 'view_node') {
          $options['links'][$field] = $field_names[$field];
        }

        $blazies = strpos($handler['field'], 'blazy_') !== FALSE;
        if ($blazies) {
          $options['images'][$field] = $field_names[$field];
          $options['overlays'][$field] = $field_names[$field];
          $options['thumbnails'][$field] = $field_names[$field];
        }
      }

      // Captions can be anything to get custom works going.
      $options['captions'][$field] = $field_names[$field];
    }

    $definition['plugin_id'] = $this->getPluginId();
    $definition['settings'] = $this->options;
    $definition['current_view_mode'] = $this->view->current_display;

    // Provides the requested fields.
    foreach ($definitions as $key) {
      $definition[$key] = isset($options[$key]) ? $options[$key] : [];
    }

    return $definition;
  }

  /**
   * Returns an individual row/element content.
   */
  public function buildElement(array &$element, $row, $index) {
    $settings = &$element['settings'];
    $item_id  = empty($settings['item_id']) ? 'box' : $settings['item_id'];

    // Add main image fields if so configured.
    if (!empty($settings['image'])) {
      // Supports individual grid/box image style either inline IMG, or CSS.
      $image             = $this->getImageRenderable($settings, $row, $index);
      $element['item']   = $this->getImageItem($image);
      $element[$item_id] = empty($image['rendered']) ? [] : $image['rendered'];
    }

    // Add caption fields if so configured.
    $element['caption'] = $this->getCaption($index, $settings);

    // Add layout field, may be a list field, or builtin layout options.
    if (!empty($settings['layout'])) {
      $this->getLayout($settings, $index);
    }
  }

  /**
   * Returns the modified renderable image_formatter to support lazyload.
   */
  public function getImageRenderable(array &$settings, $row, $index) {
    $image = $this->isImageRenderable($row, $index, $settings['image']);

    /* @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
    if (empty($image['raw'])) {
      return $image;
    }

    // If the image has #item property, lazyload may work, otherwise skip.
    // This hustle is to lazyload tons of images -- grids, large galleries,
    // gridstack, mason, with multimedia/ lightboxes for free.
    if ($item = $this->getImageItem($image)) {
      // Supports multiple image styles within a single view such as GridStack,
      // else fallbacks to the defined image style if available.
      if (empty($settings['image_style'])) {
        $image_style = isset($image['rendered']['#image_style']) ? $image['rendered']['#image_style'] : '';
        $settings['image_style'] = empty($settings['image_style']) ? $image_style : $settings['image_style'];
      }

      // Converts image formatter for blazy to reduce complexity with CSS
      // background option, and other options, and still lazyload it.
      $theme = isset($image['rendered']['#theme']) ? $image['rendered']['#theme'] : '';
      if (in_array($theme, ['blazy', 'image_formatter'])) {
        $settings['cache_tags'] = isset($image['rendered']['#cache']['tags']) ? $image['rendered']['#cache']['tags'] : [];

        if ($theme == 'blazy') {
          // Pass Blazy field formatter settings into Views style plugin.
          // This allows richer contents such as multimedia/ lightbox for free.
          // Yet, ensures the Views style plugin wins over Blazy formatter,
          // such as with GridStack which may have its own breakpoints.
          $item_settings = array_filter($image['rendered']['#build']['settings']);
          $settings = array_filter($settings);
          $settings = array_merge($item_settings, $settings);
        }
        elseif ($theme == 'image_formatter') {
          // Deals with "link to content/image" by formatters.
          $settings['content_url'] = isset($image['rendered']['#url']) ? $image['rendered']['#url'] : '';
          if (empty($settings['media_switch']) && !empty($settings['content_url'])) {
            $settings['media_switch'] = 'content';
          }
        }

        // Rebuilds the image for the brand new richer Blazy.
        // With the working Views cache, nothing to worry much.
        $build = ['item' => $item, 'settings' => $settings];
        $image['rendered'] = $this->blazyManager->getImage($build);
      }
    }

    return $image;
  }

  /**
   * Checks if we can work with this formatter, otherwise no go if flattened.
   */
  public function isImageRenderable($row, $index, $field_image = '') {
    if (!empty($field_image) && $image = $this->getFieldRenderable($row, $index, $field_image)) {
      if ($item = $this->getImageItem($image)) {
        return $image;
      }

      // Dump Video embed thumbnail/video/colorbox as is.
      if (isset($image['rendered'])) {
        return $image;
      }
    }
    return [];
  }

  /**
   * Get the image item to work with out of this formatter.
   *
   * All this mess is because Views may render/flatten images earlier.
   */
  public function getImageItem($image) {
    // Image formatter.
    $item = empty($image['rendered']['#item']) ? [] : $image['rendered']['#item'];

    // Blazy formatter.
    if (isset($image['rendered']['#build'])) {
      $item = $image['rendered']['#build']['item'];
    }

    // Don't know other reasonable formatters to work with.
    if (!is_object($item)) {
      return [];
    }
    return $item;
  }

  /**
   * Returns the rendered caption fields.
   */
  public function getCaption($index, $settings = []) {
    $items = [];
    $keys  = array_keys($this->view->field);
    if (!empty($settings['caption'])) {
      $caption_items = [];
      foreach ($settings['caption'] as $key => $caption) {
        $caption_rendered = $this->getField($index, $caption);
        if (empty($caption_rendered)) {
          continue;
        }

        if (in_array($caption, array_values($keys))) {
          $caption_items[$key]['#markup'] = $caption_rendered;
        }
      }
      $items['data'] = $caption_items;
    }

    $items['link']  = empty($settings['link']) ? [] : $this->getFieldRendered($index, $settings['link']);
    $items['title'] = empty($settings['title']) ? [] : $this->getFieldRendered($index, $settings['title'], TRUE);

    if (!empty($settings['overlay'])) {
      $items['overlay'] = $this->getFieldRendered($index, $settings['overlay']);
    }

    return $items;
  }

  /**
   * Returns the rendered layout fields.
   */
  public function getLayout(array &$settings, $index) {
    if (strpos($settings['layout'], 'field_') !== FALSE) {
      $settings['layout'] = strip_tags($this->getField($index, $settings['layout']));
    }
  }

  /**
   * Returns the rendered field, either string or array.
   */
  public function getFieldRendered($index, $field_name = '', $restricted = FALSE) {
    if (!empty($field_name) && $output = $this->getField($index, $field_name)) {
      return is_array($output) ? $output : ['#markup' => ($restricted ? Xss::filterAdmin($output) : $output)];
    }
    return [];
  }

  /**
   * Returns the renderable array of field containing rendered and raw data.
   */
  public function getFieldRenderable($row, $index, $field_name = '', $multiple = FALSE) {
    if (empty($field_name)) {
      return FALSE;
    }

    // Be sure to not check "Use field template" under "Style settings" to have
    // renderable array to work with, otherwise flattened string!
    $result = isset($this->view->field[$field_name]) ? $this->view->field[$field_name]->getItems($row) : [];
    return empty($result) ? [] : ($multiple ? $result : $result[0]);
  }

  /**
   * Returns the string values for the expected Title, ET label, List, Term.
   *
   * @todo re-check this, or if any consistent way to retrieve string values.
   */
  public function getFieldString($row, $field_name, $index) {
    $values   = [];
    $renderer = $this->blazyManager->getRenderer();

    // Content title/List/Text, either as link or plain text.
    if ($value = $this->getFieldValue($index, $field_name)) {
      $value = is_array($value) ? array_filter($value) : $value;

      // Entity reference label.
      if (empty($value) && $markup = $this->getField($index, $field_name)) {
        $value = is_object($markup) ? trim(strip_tags($markup->__toString())) : $value;
      }

      // Tags has comma separated value, although can be changed, just too much.
      if (strpos($value, ',') !== FALSE) {
        $tags = explode(',', $value);
        $rendered_tags = [];
        foreach ($tags as $tag) {
          $rendered_tags[] = Html::cleanCssIdentifier(Unicode::strtolower(trim($tag)));
        }
        $values[$index] = implode(' ', $rendered_tags);
      }
      else {
        $value = is_string($value) ? $value : (isset($value[0]['value']) && !empty($value[0]['value']) ? $value[0]['value'] : '');
        $values[$index] = empty($value) ? '' : Html::cleanCssIdentifier(Unicode::strtolower($value));
      }
    }

    // Term reference/ET, either as link or plain text.
    if (empty($values)) {
      if ($renderable = $this->getFieldRenderable($row, $field_name, TRUE)) {
        $value = [];
        foreach ($renderable as $key => $render) {
          $class = isset($render['rendered']['#title']) ? $render['rendered']['#title'] : $renderer->render($render['rendered']);
          $class = trim(strip_tags($class));
          $value[$key] = Html::cleanCssIdentifier(Unicode::strtolower($class));
        }
        $values[$index] = empty($value) ? '' : implode(' ', $value);
      }
    }
    return $values;
  }

}

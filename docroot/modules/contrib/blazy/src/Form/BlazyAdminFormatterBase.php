<?php

namespace Drupal\blazy\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormState;
use Drupal\Component\Utility\Unicode;

/**
 * A base for field formatter admin to have re-usable methods in one place.
 */
abstract class BlazyAdminFormatterBase extends BlazyAdminBase {

  /**
   * Returns re-usable image formatter form elements.
   */
  public function imageStyleForm(array &$form, $definition = []) {
    $image_styles  = image_style_options(FALSE);
    $is_responsive = function_exists('responsive_image_get_image_dimensions');

    if (empty($definition['no_image_style'])) {
      $form['image_style'] = $this->baseForm($definition)['image_style'];
    }

    if (!empty($definition['thumbnail_style'])) {
      $form['thumbnail_style'] = $this->baseForm($definition)['thumbnail_style'];
    }

    if ($is_responsive && !empty($definition['responsive_image'])) {
      $form['responsive_image_style'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Responsive image'),
        '#options'     => $this->getResponsiveImageOptions(),
        '#description' => $this->t('Responsive image style for the main stage image is more reasonable for large images. Works with multi-serving IMG, or PICTURE element. Not compatible with breakpoints and aspect ratio, yet. Leave empty to disable.'),
        '#access'      => $this->getResponsiveImageOptions(),
        '#weight'      => -100,
      ];

      if (!empty($definition['background'])) {
        $form['background']['#states'] = $this->getState(static::STATE_RESPONSIVE_IMAGE_STYLE_DISABLED, $definition);
      }
    }

    if (!empty($definition['thumbnail_effect'])) {
      $form['thumbnail_effect'] = [
        '#type'    => 'select',
        '#title'   => $this->t('Thumbnail effect'),
        '#options' => isset($definition['thumbnail_effect']) ? $definition['thumbnail_effect'] : [],
        '#weight'  => -100,
      ];
    }

    if ($is_responsive && isset($form['responsive_image_style'])) {
      $url = Url::fromRoute('entity.responsive_image_style.collection')->toString();
      $form['responsive_image_style']['#description'] .= ' ' . $this->t('<a href=":url" target="_blank">Manage responsive image styles</a>.', [':url' => $url]);
    }
  }

  /**
   * Return the field formatter settings summary.
   */
  public function settingsSummary($plugin, $definition = []) {
    $form         = [];
    $summary      = [];
    $form_state   = new FormState();
    $settings     = isset($definition['settings']) ? $definition['settings'] : $plugin->getSettings();
    $elements     = $plugin->settingsForm($form, $form_state);
    $image_styles = image_style_options(TRUE);
    $breakpoints  = isset($settings['breakpoints']) ? array_filter($settings['breakpoints']) : [];
    $excludes     = empty($definition['excludes']) ? $definition : $definition['excludes'];

    unset($image_styles['']);

    $extras = ['details', 'fieldset', 'hidden', 'markup', 'item', 'table'];
    foreach ($settings as $key => $setting) {
      $type = isset($elements[$key]['#type']) ? $elements[$key]['#type'] : '';

      if (!empty($excludes) && in_array($key, $excludes)) {
        continue;
      }

      if (in_array($type, $extras) || empty($type)) {
        continue;
      }

      $access   = isset($elements[$key]['#access']) ? $elements[$key]['#access'] : TRUE;
      $title    = !isset($elements[$key]) && isset($settings[$key]) ? Unicode::ucfirst(str_replace('_', ' ', $key)) : '';
      $title    = isset($elements[$key]['#title']) ? $elements[$key]['#title'] : $title;
      $options  = isset($elements[$key]['#options']) ? $elements[$key]['#options'] : [];
      $vanilla  = !empty($settings['vanilla']) && !isset($elements[$key]['#enforced']);
      $multiple = isset($elements[$key]['#multiple']) && $elements[$key]['#multiple'];

      if ($key == 'breakpoints') {
        $widths = [];
        if ($breakpoints) {
          foreach ($breakpoints as $id => $breakpoint) {
            if (!empty($breakpoint['width'])) {
              $widths[] = $breakpoint['width'];
            }
          }
        }

        $title   = $this->t('Breakpoints');
        $setting = $widths ? implode(', ', $widths) : $this->t('None');
      }
      else {
        if (empty($title) || $vanilla || !$access) {
          continue;
        }

        if ($key == 'override' && empty($setting)) {
          unset($settings['overridables']);
        }

        if (is_bool($setting) && $setting) {
          $setting = $this->t('Yes');
        }
        elseif (is_string($setting) && $key != 'cache') {
          // The value is based on select options.
          if (!$multiple && $type == 'select' && isset($options[$setting])) {
            $setting = is_object($options[$setting]) ? $options[$setting]->render() : $options[$setting];
          }
        }
        elseif (is_array($setting)) {
          $values = array_filter($setting);

          if (!empty($values)) {
            // Combine possible multi-value select, or checkboxes.
            $multiple_values = array_combine($values, $values);

            foreach ($multiple_values as $i => $value) {
              if (isset($options[$i])) {
                $multiple_values[$i] = is_object($options[$i]) ? $options[$i]->render() : $options[$i];
              }
            }

            $setting = implode(', ', $multiple_values);
          }

          if (is_array($setting)) {
            $setting = array_filter($setting);
            if (!empty($setting)) {
              $setting = implode(', ', $setting);
            }
          }
        }

        if ($key == 'cache') {
          $setting = $this->getCacheOptions()[$setting];
        }
      }

      if (empty($setting)) {
        continue;
      }

      if (isset($settings[$key])) {
        $summary[] = $this->t('@title: <strong>@setting</strong>', [
          '@title'   => $title,
          '@setting' => $setting,
        ]);
      }
    }
    return $summary;
  }

  /**
   * Returns available fields for select options.
   */
  public function getFieldOptions($target_bundles = [], $allowed_field_types = [], $entity_type = 'media', $target_type = '') {
    $options = [];
    $storage = $this->blazyManager()->getEntityTypeManager()->getStorage('field_config');

    // Fix for Views UI not recognizing Media bundles, unlike Formatters.
    if (empty($target_bundles)) {
      $bundle_service = \Drupal::service('entity_type.bundle.info');
      $target_bundles = $bundle_service->getBundleInfo($entity_type);
    }

    // Declutters options from less relevant options.
    $excludes = $this->getExcludedFieldOptions();

    foreach ($target_bundles as $bundle => $label) {
      if ($fields = $storage->loadByProperties(['entity_type' => $entity_type, 'bundle' => $bundle])) {
        foreach ((array) $fields as $field_name => $field) {
          if (in_array($field->getName(), $excludes)) {
            continue;
          }
          if (empty($allowed_field_types)) {
            $options[$field->getName()] = $field->getLabel();
          }
          elseif (in_array($field->getType(), $allowed_field_types)) {
            $options[$field->getName()] = $field->getLabel();
          }

          if (!empty($target_type) && ($field->getSetting('target_type') == $target_type)) {
            $options[$field->getName()] = $field->getLabel();
          }
        }
      }
    }

    return $options;
  }

  /**
   * Declutters options from less relevant options.
   */
  public function getExcludedFieldOptions() {
    $excludes = 'field_document_size field_id field_media_in_library field_mime_type field_source field_tweet_author field_tweet_id field_tweet_url field_media_video_embed_field field_instagram_shortcode field_instagram_url';
    $excludes = explode(' ', $excludes);
    $excludes = array_combine($excludes, $excludes);

    $this->blazyManager->getModuleHandler()->alter('blazy_excluded_field_options', $excludes);
    return $excludes;
  }

  /**
   * Returns Responsive image for select options.
   */
  public function getResponsiveImageOptions() {
    $options = [];
    if ($this->blazyManager()->getModuleHandler()->moduleExists('responsive_image')) {
      $image_styles = $this->blazyManager()->entityLoadMultiple('responsive_image_style');
      if (!empty($image_styles)) {
        foreach ($image_styles as $name => $image_style) {
          if ($image_style->hasImageStyleMappings()) {
            $options[$name] = strip_tags($image_style->label());
          }
        }
      }
    }
    return $options;
  }

}

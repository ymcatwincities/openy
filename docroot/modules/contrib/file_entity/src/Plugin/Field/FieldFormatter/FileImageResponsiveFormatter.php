<?php

namespace Drupal\file_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Plugin for responsive image formatter.
 *
 * @FieldFormatter(
 *   id = "file_image_responsive",
 *   label = @Translation("Responsive image"),
 *   field_types = {
 *     "uri",
 *   }
 * )
 */
class FileImageResponsiveFormatter extends ImageFormatter {

  /**
   * @var EntityStorageInterface
   */
  protected $responsiveImageStyleStorage;

  /*
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Constructs a ResponsiveImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   *   The responsive image style storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $responsive_image_style_storage, EntityStorageInterface $image_style_storage, LinkGeneratorInterface $link_generator, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);

    $this->responsiveImageStyleStorage = $responsive_image_style_storage;
    $this->imageStyleStorage = $image_style_storage;
    $this->linkGenerator = $link_generator;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('responsive_image_style'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('link_generator'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'responsive_image_style' => '',
      'image_link' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $file = $items->getEntity();

    // Collect cache tags to be added for each item in the field.
    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    $image_styles_to_load = array();
    $cache_tags = [];
    if ($responsive_image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    // Extract field item attributes for the theme function, and unset them
    // from the $item so that the field template does not re-render them.
    $item = $file->_referringItem;
    $item_attributes = $item->_attributes;
    unset($item->_attributes);

    if ($this->getSetting('image_link')) {
      $url = file_url_transform_relative(file_create_url($file->getFileUri()));
    }

    $elements[] = array(
      '#theme' => 'responsive_image_formatter',
      '#item' => $item,
      '#item_attributes' => $item_attributes,
      '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
      '#url' => !empty($url) ? $url : NULL,
      '#cache' => array(
        'tags' => $cache_tags,
      ),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {}

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $responsive_image_options = array();
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    if ($responsive_image_styles && !empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    $elements['responsive_image_style'] = array(
      '#title' => t('Responsive image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('responsive_image_style'),
      '#required' => TRUE,
      '#options' => $responsive_image_options,
      '#description' => array(
        '#markup' => $this->linkGenerator->generate($this->t('Configure Responsive Image Styles'), new Url('entity.responsive_image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer responsive image styles'),
      ),
    );

    unset($elements['image_link']['#options']['content']);
    unset($elements['image_style']);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    if ($responsive_image_style) {
      $summary[] = t('Responsive image style: @responsive_image_style', array('@responsive_image_style' => $responsive_image_style->label()));

      $link_types = array(
        'file' => t('Linked to file'),
      );
      // Display this setting only if image is linked.
      if (isset($link_types[$this->getSetting('image_link')])) {
        $summary[] = $link_types[$this->getSetting('image_link')];
      }
    }
    else {
      $summary[] = t('Select a responsive image style.');
    }

    return $summary;
  }

}

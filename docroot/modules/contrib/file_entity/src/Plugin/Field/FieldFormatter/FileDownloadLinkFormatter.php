<?php

namespace Drupal\file_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Utility\Token;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_download_link' formatter.
 *
 * @FieldFormatter(
 *   id = "file_download_link",
 *   label = @Translation("Download link"),
 *   description = @Translation("Displays a link that will force the browser to download the file."),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileDownloadLinkFormatter extends FileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $module_handler;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a FileDownloadLinkFormatter instance.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param Drupal\Core\Render\RendererInterface $renderer
   *   The rendered service
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RendererInterface $renderer, ModuleHandlerInterface $module_handler, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->renderer = $renderer;
    $this->module_handler = $module_handler;
    $this->token = $token;
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
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['access_message'] = [
      '#type' => 'textfield',
      '#title' => t('No access message'),
      '#description' => t("This text is shown instead of the download link if the user doesn't have permission to download the file."),
      '#default_value' => $this->getSetting('access_message'),
    ];
    $element['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Link text'),
      '#description' => t('This field supports tokens.'),
      '#default_value' => $this->getSetting('text'),
    );
    // If we have the token module available, add the token tree link.
    if ($this->module_handler->moduleExists('token')) {
      $token_types = array('file');
      if (!empty($form['#entity_type'])) {
        $token_types[] = $form['#entity_type'];
      }
      $element['token_tree_link'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => $token_types,
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'access_message' => "You don't have access to download this file.",
      'text' => 'Download [file:name]',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    // For token replace, we also want to use the parent entity of the file.
    $parent_entity = $items->getParent()->getValue();
    if (!empty($parent_entity)) {
      $parent_entity_type = $parent_entity->getEntityType()->id();
      $token_data[$parent_entity_type] = $parent_entity;
    }
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      // Prepare the attributes for the main container of the template.
      $attributes = new Attribute();

      // Prepare the text and the URL of the link.
      $mime_type = $file->getMimeType();
      $token_data['file'] = $file;
      $link_text = $this->token->replace($this->getSetting('text'), $token_data);
      // Set options as per anchor format described at
      // http://microformats.org/wiki/file-format-examples
      $download_url = $file->downloadUrl(array('attributes' => array('type' => $mime_type . '; length=' . $file->getSize())));
      if ($file->access('download')) {
        $elements[$delta] = [
          '#theme' => 'file_entity_download_link',
          '#file' => $file,
          '#download_link' => Link::fromTextAndUrl($link_text, $download_url),
          '#icon' => file_icon_class($mime_type),
          '#attributes' => $attributes,
          '#file_size' => format_size($file->getSize()),
        ];
      }
      else {
        $elements[$delta] = [
          '#markup' => $this->getSetting('access_message'),
        ];
      }
      $this->renderer->addCacheableDependency($elements[$delta], $file);
    }

    return $elements;
  }

}

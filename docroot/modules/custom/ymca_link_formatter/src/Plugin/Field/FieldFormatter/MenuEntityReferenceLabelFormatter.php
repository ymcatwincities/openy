<?php

namespace Drupal\ymca_link_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_label_url",
 *   label = @Translation("Menu Item Route URL"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MenuEntityReferenceLabelFormatter extends EntityReferenceFormatterBase {

  /**
   * Entity has been processed.
   *
   * @var \Drupal\menu_link_content\Entity\MenuLinkContent
   */
  private $entity;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'route_link' => TRUE,
      'route_title' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['route_link'] = array(
      '#title' => t('Link label to the route link'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('route_link'),
    );
    $elements['route_title'] = array(
      '#title' => t('Title for the route link'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('route_title'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->getSetting('route_link') ? t(
      'Link to the referenced entity'
    ) : t('No link');
    $summary[] = ($this->getSetting('route_title') == '') ? t(
      'Menu title for the route link'
    ) : t('Custom title for the route link');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $output_as_link = $this->getSetting('route_link');

    foreach ($this->getEntitiesToView(
      $items,
      $langcode
    ) as $delta => $this->entity) {
      $label = ($this->getSetting('route_title') == '') ? $this->entity->label(
      ) : $this->getSetting('route_title');
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($output_as_link && !$this->entity->isNew()) {
        try {

          /* @var \Drupal\Core\Field\FieldItemList $route */
          $route = $this->entity->get('link');

          // We are dealing only with first item here.
          /* @var \Drupal\link\Plugin\Field\FieldType\LinkItem $item */
          $item = $route->get(0);
          $uri = $item->getUrl();
        }
        catch (UndefinedLinkTemplateException $e) {
          // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
          // and it means that the entity type doesn't have a link template nor
          // a valid "uri_callback", so don't bother trying to output a link for
          // the rest of the referenced entities.
          $output_as_link = FALSE;
        }
      }

      if ($output_as_link && isset($uri) && !$this->entity->isNew()) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => new FormattableMarkup($label, []),
          '#url' => $uri,
          '#options' => $uri->getOptions(),
        ];

        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += array('attributes' => array());
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and shouldn't be rendered in the field template.
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = array('#plain_text' => $label);
      }
      $elements[$delta]['#cache']['tags'] = $this->entity->getCacheTags();
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity) {
    return new AccessResultAllowed();
  }

}

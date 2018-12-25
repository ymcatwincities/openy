<?php

namespace Drupal\fontyourface\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\fontyourface\FontInterface;

/**
 * Defines the Font entity.
 *
 * @ingroup fontyourface
 *
 * @ContentEntityType(
 *   id = "font",
 *   label = @Translation("Font"),
 *   handlers = {
 *     "storage_schema" = "Drupal\fontyourface\FontStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\fontyourface\FontListBuilder",
 *     "views_data" = "Drupal\fontyourface\Entity\FontViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\fontyourface\Form\FontForm",
 *       "edit" = "Drupal\fontyourface\Form\FontForm",
 *     },
 *     "access" = "Drupal\fontyourface\FontAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\fontyourface\FontHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "fontyourface_font",
 *   admin_permission = "administer font entities",
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "pid" = "pid",
 *     "url" = "url",
 *   },
 *   links = {
 *     "canonical" = "/admin/appearance/font/{font}",
 *     "enable" = "/admin/appearance/font/{font}/{js}/enable",
 *     "disable" = "/admin/appearance/font/{font}/{js}/disable",
 *     "collection" = "/admin/appearance/font",
 *   },
 *   field_ui_base_route = "font.settings",
 *   render_cache = FALSE
 * )
 */
class Font extends ContentEntityBase implements FontInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->get('pid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvider($provider) {
    $this->set('pid', $provider);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata() {
    return unserialize($this->get('metadata')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setMetadata($metadata) {
    $this->set('metadata', serialize($metadata));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActivated() {
    $config = \Drupal::config('fontyourface.settings');
    $enabled_fonts = $config->get('enabled_fonts');
    return in_array($this->url->value, $enabled_fonts);
  }

  /**
   * {@inheritdoc}
   */
  public function isDeactivated() {
    return !$this->isActivated();
  }

  /**
   * {@inheritdoc}
   */
  public function activate() {
    $config = \Drupal::configFactory()->getEditable('fontyourface.settings');
    if (!$this->isActivated()) {
      $enabled_fonts = $config->get('enabled_fonts');
      $enabled_fonts[] = $this->url->value;
      $config->set('enabled_fonts', $enabled_fonts)
        ->save();
    }
    $this->status->value = TRUE;
    $this->save();
    return $this->isActivated();
  }

  /**
   * {@inheritdoc}
   */
  public function deactivate() {
    $config = \Drupal::configFactory()->getEditable('fontyourface.settings');
    $enabled_fonts = $config->get('enabled_fonts');
    $enabled_fonts = array_diff($enabled_fonts, [$this->url->value]);
    $config->set('enabled_fonts', $enabled_fonts)
      ->save();
    $this->status->value = FALSE;
    $this->save();
    return $this->isDeactivated();
  }

  /**
   * {@inheritdoc}
   */
  public static function loadActivatedFonts() {
    $config = \Drupal::config('fontyourface.settings');
    $enabled_fonts = $config->get('enabled_fonts');
    $fonts = [];
    foreach ($enabled_fonts as $enabled_font_url) {
      $font = self::loadByUrl($enabled_font_url);
      if (!empty($font)) {
        $fonts[$font->url->value] = $font;
      }
    }
    return $fonts;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByUrl($font_url) {
    $fonts = \Drupal::entityManager()->getStorage('font')->loadByProperties(['url' => $font_url]);
    return reset($fonts);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Font entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Font entity.'))
      ->setReadOnly(TRUE);
    $fields['pid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Provider ID'))
      ->setDescription(t('The font provider ID.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Font URL'))
      ->setDescription(t('A URL for the font.'))
      ->setSettings([
        'max_length' => 191,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Font entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['css_family'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CSS Family'))
      ->setDescription(t('CSS family for the font.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['css_style'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CSS Style'))
      ->setDescription(t('CSS style for the font.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['css_weight'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CSS Weight'))
      ->setDescription(t('CSS weight for the font.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['foundry'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Foundry'))
      ->setDescription(t('Foundry for the font.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['foundry_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Foundry URL'))
      ->setDescription(t('Foundry URL.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['license'] = BaseFieldDefinition::create('string')
      ->setLabel(t('License'))
      ->setDescription(t('Font License.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['license_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('License URL'))
      ->setDescription(t('License URL.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['designer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Designer'))
      ->setDescription(t('Font Designer'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['designer_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Designer URL'))
      ->setDescription(t('Designer URL.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Font Metadata'))
      ->setDescription(t('Additional Font Metadata'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Font status'))
      ->setDescription(t('A boolean indicating whether the font is enabled. Mostly used for views.'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(FALSE);

    return $fields;
  }

}

<?php

namespace Drupal\local_fonts;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\fontyourface\Entity\Font;

/**
 * Provides a listing of Custom Font entities.
 */
class LocalFontConfigEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Custom Font');
    $header['id'] = $this->t('Machine name');
    $header['font_family'] = $this->t('Font Family');
    $header['font_view'] = $this->t('View Font');
    $header['font_manage'] = $this->t('Enable/Disable');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['font_family'] = $entity->font_family;
    try {
      $font = Font::loadByUrl('local_fonts://' . $entity->id());
      $parameters = [
        'js' => 'nojs',
        'font' => $font->id(),
      ];
      $options = [
        'query' => \Drupal::destination()->getAsArray(),
      ];
      $row['font_view'] = Link::fromTextAndUrl($this->t('View Font'), $font->toUrl('canonical'));
      if ($font->isActivated()) {
        $url = Url::fromRoute('entity.font.deactivate', $parameters, $options);
        $row['font_manage'] = Link::fromTextAndUrl($this->t('Disable'), $url);
      }
      else {
        $url = Url::fromRoute('entity.font.activate', $parameters, $options);
        $row['font_manage'] = Link::fromTextAndUrl($this->t('Enable'), $url);
      }
    }
    catch (Exception $e) {
      $row['font_view'] = $this->t('Disabled');
      $row['font_manage'] = $this->t('Disabled');
    }
    return $row + parent::buildRow($entity);
  }

}

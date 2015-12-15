<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\filter\SearchApiLanguage.
 */

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Core\Language\LanguageInterface;

/**
 * Defines a filter for filtering on the language of items.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_language")
 */
class SearchApiLanguage extends SearchApiFilterOptions {

  /**
   * {@inheritdoc}
   */
  protected function getValueOptions() {
    parent::getValueOptions();
    $this->valueOptions = array(
      'content' => $this->t('Current content language'),
      'interface' => $this->t('Current interface language'),
      'default' => $this->t('Default site language'),
    ) + $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!is_array($this->value)) {
      $this->value = $this->value ? array($this->value) : array();
    }
    foreach ($this->value as $i => $v) {
      if ($v == 'content') {
        $this->value[$i] = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      }
      elseif ($v == 'interface') {
        $this->value[$i] = \Drupal::languageManager()->getCurrentLanguage()->getId();
      }
      elseif ($v == 'default') {
        $this->value[$i] = \Drupal::languageManager()->getDefaultLanguage()->getId();
      }
    }
    parent::query();
  }

}

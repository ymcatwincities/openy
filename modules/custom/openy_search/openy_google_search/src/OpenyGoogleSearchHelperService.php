<?php

namespace Drupal\openy_google_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Class OpenyGoogleSearchHelperService.
 */
class OpenyGoogleSearchHelperService {

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Constructs a new OpenyGoogleSearchHelperService object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *  The langUAGE MANAGER
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *  The config factory.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *  The path alias manager
   */
  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager) {
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->aliasManager = $alias_manager;
  }

  /**
   * Gets alias for search results page id saved in module settings config.
   *
   * @return string
   */
  public function getSearchResultsPageAlias() {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $search_config = $this->configFactory->get('openy_google_search.settings');
    $page_id = $search_config->get('search_page_id');
    $search_alias = $this->aliasManager->getAliasByPath('/node/' . $page_id, $language);
    return ltrim($search_alias, '/');
  }

}

<?php
/**
 * @file
 * Contains the SearchApiSpellcheckSolr class.
 */

/**
 * Spellcheck class which can provide spelling suggestions. The constructor
 * populates the instance with any suggestions returned by Solr.
 */
class SearchApiSpellcheckSolr extends SearchApiSpellcheck {

  /**
   * Constructs a SearchApiSpellcheckSolr object.
   *
   * If Solr has returned spelling suggestion then loop through them and add
   * them to this spellcheck service.
   *
   * @param object $response
   *   The Solr response object.
   */
  function __construct($response) {
    if (isset($response->spellcheck->suggestions)) {
      $suggestions = $response->spellcheck->suggestions;
      foreach ($suggestions as $word => $data) {
        foreach ($data->suggestion as $suggestion) {
          $this->addSuggestion(new SearchApiSpellcheckSuggestion($word, $suggestion));
        }
      }
    }
  }

}

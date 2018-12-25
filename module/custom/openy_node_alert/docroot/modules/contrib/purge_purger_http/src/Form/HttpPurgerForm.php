<?php

namespace Drupal\purge_purger_http\Form;

use Drupal\purge_purger_http\Form\HttpPurgerFormBase;

/**
 * Configuration form for the HTTP Bundled Purger.
 */
class HttpPurgerForm extends HttpPurgerFormBase {

  /**
   * The token group names this purger supports replacing tokens for.
   *
   * @var string[]
   *
   * @see purge_tokens_token_info()
   */
  protected $tokenGroups = ['invalidation'];

}

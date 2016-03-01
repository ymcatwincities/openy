<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Form\HttpBundledPurgerForm.
 */

namespace Drupal\purge_purger_http\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\purge_purger_http\Form\HttpPurgerFormBase;
use Drupal\purge_purger_http\Entity\HttpPurgerSettings;

/**
 * Configuration form for the HTTP Bundled Purger.
 */
class HttpBundledPurgerForm extends HttpPurgerFormBase {

  /**
   * The token group names this purger supports replacing tokens for.
   *
   * @see purge_tokens_token_info()
   *
   * @var string[]
   */
  protected $tokenGroups = ['invalidations'];

}

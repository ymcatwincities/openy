<?php

namespace Drupal\purge_purger_http\Tests;

use Drupal\purge_purger_http\Tests\HttpPurgerFormTestBase;

/**
 * Tests \Drupal\purge_purger_http\Form\HttpPurgerForm.
 *
 * @group purge_purger_http
 */
class HttpPurgerFormTest extends HttpPurgerFormTestBase {

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass = 'Drupal\purge_purger_http\Form\HttpPurgerForm';

  /**
   * The plugin ID for which the form tested is rendered for.
   *
   * @var string
   */
  protected $plugin = 'http';

  /**
   * The token group names the form is supposed to display.
   *
   * @var string[]
   *
   * @see purge_tokens_token_info()
   */
  protected $tokenGroups = ['invalidation'];

}

<?php

namespace Drupal\tango_card\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Tango Card catalog page display.
 */
class CatalogPageController extends ControllerBase {

  /**
   * The Tango Card wrapper.
   *
   * @var \Drupal\tango_card\TangoCardWrapper
   */
  protected $tangoCardWrapper;

  /**
   * Constructs the CatalogPageController object.
   *
   * @param \Drupal\tango_card\TangoCardWrapper $tango_card_wrapper
   *   The Tango Card wrapper.
   */
  public function __construct(TangoCardWrapper $tango_card_wrapper) {
    $this->tangoCardWrapper = $tango_card_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tango_card.tango_card_wrapper')
    );
  }

  /**
   * Return Tango Card catalog page.
   *
   * @return array
   *   A renderable array.
   */
  public function pageView() {
    try {
      $brands = $this->tangoCardWrapper->listRewards();
      $success = $brands !== FALSE;
    }
    catch (Exception $e) {
      $success = FALSE;
    }

    if (!$success) {
      $link = new Link($this->t('settings page'), Url::fromRoute('tango_card.settings'));
      $args = array('!link' => $link->toString());

      return array(
        '#theme' => 'status_messages',
        '#message_list' => array(
          'warning' => array(
            $this->t('The request could not be made. Make sure Tango Card credentials are properly registered on !link.', $args),
          ),
        ),
      );
    }

    $rows = array();
    foreach ($brands as $brand) {
      $img = array('#theme' => 'image', '#uri' => $brand->image_url);
      $rows[] = array(render($img), $brand->description);
    }

    $build = array(
      '#theme' => 'table',
      '#rows' => $rows,
    );

    return $build;
  }

}

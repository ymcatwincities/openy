<?php

namespace Drupal\geolocation\EventSubscriber;

use Drupal\views\Ajax\ViewAjaxResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber to handle AJAX responses.
 */
class AjaxResponseSubscriber implements EventSubscriberInterface {

  /**
   * Alter the views AJAX response commands only for the map.
   *
   * @param array $commands
   *   An array of commands to alter.
   */
  protected function alterCommands(array &$commands) {
    foreach ($commands as $delta => &$command) {
      // Substitute the 'replace' method without our custom jQuery method which
      // will allow views content to be injected one after the other.
      if (
        isset($command['method'])
        && $command['method'] === 'replaceWith'
        && isset($command['selector'])
        && substr($command['selector'], 0, 16) === '.js-view-dom-id-'
      ) {
        $command['command'] = 'geolocationCommonMapsUpdate';
        unset($command['method']);
      }
    }
  }

  /**
   * Renders the ajax commands right before preparing the result.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event, which contains the possible AjaxResponse object.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    // Only alter views ajax responses.
    if (!($response instanceof ViewAjaxResponse)) {
      return;
    }

    $view = $response->getView();

    if ($view->getStyle()->getPluginId() !== 'maps_common') {
      // This view is not of maps_common style, but maybe an attachment is.
      $common_map_attachment = FALSE;

      $attached_display_ids = $view->display_handler->getAttachedDisplays();
      foreach ($attached_display_ids as $display_id) {
        $current_display = $view->displayHandlers->get($display_id);
        if (!empty($current_display)) {
          if (
            !empty($current_display->getOption('style')['type'])
            && $current_display->getOption('style')['type'] == 'maps_common'
          ) {
            $common_map_attachment = TRUE;
          }
        }
      }

      if (!$common_map_attachment) {
        return;
      }
    }

    $page_change = $event->getRequest()->query->get('page', FALSE);

    $commands = &$response->getCommands();
    foreach ($commands as $delta => &$command) {
      // Substitute the 'replace' method without our custom jQuery method which
      // will allow views content to be injected one after the other.
      if (
        isset($command['method'])
        && $command['method'] === 'replaceWith'
        && isset($command['selector'])
        && substr($command['selector'], 0, 16) === '.js-view-dom-id-'
      ) {
        $command['command'] = 'geolocationCommonMapsUpdate';
        unset($command['method']);
      }

      // Stop the view from scrolling to the top of the page.
      if ($page_change === FALSE && $command['command'] === 'viewsScrollTop') {
        unset($commands[$delta]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::RESPONSE => [['onResponse']]];
  }

}

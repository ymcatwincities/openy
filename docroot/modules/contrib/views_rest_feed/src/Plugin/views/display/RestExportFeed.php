<?php

/**
 * @file
 * Contains \Drupal\views_rest_feed\Plugin\views\display\RestExportFeed.
 */

namespace Drupal\views_rest_feed\Plugin\views\display;

use Drupal\rest\Plugin\views\display\RestExport;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\ResponseDisplayPluginInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * The plugin that handles Data response callbacks for REST resources.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "rest_export_attachment",
 *   title = @Translation("REST export feed"),
 *   help = @Translation("Create a REST export resource feed."),
 *   uses_route = TRUE,
 *   admin = @Translation("REST export feed"),
 *   returns_response = TRUE
 * )
 */
class RestExportFeed extends RestExport implements ResponseDisplayPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['displays'] = array('default' => array());

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // Since we're childing off the 'path' type, we'll still *call* our
    // category 'page' but let's override it so it says feed settings.
    $categories['path'] = array(
      'title' => $this->t('Feed settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    $displays = array_filter($this->getOption('displays'));
    if (count($displays) > 1) {
      $attach_to = $this->t('Multiple displays');
    }
    elseif (count($displays) == 1) {
      $display = array_shift($displays);
      $displays = $this->view->storage->get('display');
      if (!empty($displays[$display])) {
        $attach_to = $displays[$display]['display_title'];
      }
    }

    if (!isset($attach_to)) {
      $attach_to = $this->t('None');
    }

    $options['displays'] = array(
      'category' => 'path',
      'title' => $this->t('Attach to'),
      'value' => $attach_to,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // It is very important to call the parent function here.
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'displays':
        $form['#title'] .= $this->t('Attach to');
        $displays = array();
        foreach ($this->view->storage->get('display') as $display_id => $display) {
          // @todo The display plugin should have display_title and id as well.
          if ($this->view->displayHandlers->has($display_id) && $this->view->displayHandlers->get($display_id)->acceptAttachments()) {
            $displays[$display_id] = $display['display_title'];
          }
        }
        $form['displays'] = array(
          '#title' => $this->t('Displays'),
          '#type' => 'checkboxes',
          '#description' => $this->t('The feed icon will be available only to the selected displays.'),
          '#options' => array_map('\Drupal\Component\Utility\Html::escape', $displays),
          '#default_value' => $this->getOption('displays'),
        );
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'displays':
        $this->setOption($section, $form_state->getValue($section));
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function attachTo(ViewExecutable $clone, $display_id, array &$build) {
    $displays = $this->getOption('displays');
    if (empty($displays[$display_id])) {
      return;
    }

    // Defer to the feed style; it may put in meta information, and/or
    // attach a feed icon.
    $clone->setArguments($this->view->args);
    $clone->setDisplay($this->display['id']);
    $clone->buildTitle();

    if ($plugin = $clone->display_handler->getPlugin('style')) {
      $clone->display_handler->getPlugin('style');

      if (method_exists($plugin, 'attachTo')) {
        $plugin->attachTo($build, $display_id, $clone->getUrl(), $clone->getTitle());
      }
      else {
        // Deviate from pattern set in \Drupal\views\Plugin\views\display\Feed by
        // calling attachRestExportTo() rather than $plugin->attachTo().
        $this->attachRestExportTo($build, $display_id, $clone->getUrl(), $clone->getTitle());
      }

      foreach ($clone->feedIcons as $feed_icon) {
        $this->view->feedIcons[] = $feed_icon;
      }
    }

    // Clean up.
    $clone->destroy();
    unset($clone);
  }

  public function attachRestExportTo(array &$build, $display_id, \Drupal\Core\Url $feed_url, $title) {
    $url_options = array();
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = $feed_url->setOptions($url_options)->toString();

    // Add icon to the view.
    $feed_icon = [
      '#url' => $url,
      '#title' => $title,
      '#theme' => 'feed_icon',
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => [
              Html::cleanCssIdentifier($this->contentType) . '-feed',
              'views-rest-feed'
            ],
          ],
        ],
      ],
      '#attached' => array(
        'library' => array(
          'views_rest_feed/views_rest_feed'
        ),
      ),
    ];
    $this->view->feedIcons[] = $feed_icon;

    // Attach a link to the feed, which is an alternate representation.
    $build['#attached']['html_head_link'][][] = array(
      'rel' => 'alternate',
      'type' => 'application/' . $this->contentType,
      'title' => $title,
      'href' => $url,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesLinkDisplay() {
    return TRUE;
  }
}

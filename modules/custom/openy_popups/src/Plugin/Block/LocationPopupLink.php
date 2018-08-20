<?php

namespace Drupal\openy_popups\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Block with popup link.
 *
 * @Block(
 *   id = "location_popup_link_block",
 *   admin_label = @Translation("Location popup link"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class LocationPopupLink extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'filter' => 'all',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['filter'] = [
      '#type' => 'select',
      '#title' => t('Locations filter'),
      '#options' => [
        'all' => t('Show all locations'),
        'by_class' => t('Show locations by class'),
      ],
      '#default_value' => $config['filter'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['filter'] = $form_state->getValue('filter');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $nid = $location_count = 0;
    $type = '';
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($config['filter'] == 'by_class') {
      if ($node && $node->getType() == 'class') {
        $type = 'class';
        $nid = $node->id();

        $location_count = NULL;
        if (\Drupal::hasService('session_instance.manager')) {
          $location_count = \Drupal::service('session_instance.manager')
            ->getLocationCountByClassNode($node);
        }
      }
    }
    if ($node && $node->getType() == 'program_subcategory') {
      $type = 'category';
      $nid = $node->id();
    }

    $block = [
      'location_popup_link' => [
        '#lazy_builder' => [
          /* @see \Drupal\openy_popups\PopupLinkGenerator */
          'openy_popups.popup_link_generator:generateLink',
          [$type, $nid],
        ],
        '#create_placeholder' => TRUE,
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args:location',
        ],
      ],
      '#attached' => [
        'library' => [
          'openy_popups/openy_popups.autoload',
        ],
        'drupalSettings' => [
          'openy_popups' => [
            'location_count' => $location_count,
          ],
        ],
      ],
    ];

    return $block;
  }

}

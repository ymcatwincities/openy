<?php

/**
 * @file
 * Contains \Drupal\page_manager_test\Plugin\Block\TestBlock.
 */

namespace Drupal\page_manager_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block to test page_manager.
 *
 * @Block(
 *   id = "page_manager_test_block",
 *   admin_label = @Translation("Page Manager Test Block")
 * )
 */
class TestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['#markup'] = $this->t('Example output');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['example'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example'),
      '#ajax' => [
        'callback' => [$this, 'exampleAjaxCallback'],
      ]
    ];
    return $form;
  }

  /**
   * Example ajax callback.
   */
  public function exampleAjaxCallback($form, FormStateInterface $form_state) {
  }

}

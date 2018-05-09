<?php
/**
 * Contains Custom block.
 */

namespace Drupal\openy_node_social_post\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "social_post_list",
 *   admin_label = @Translation("Social Post List"),
 *   category = @Translation("Custom"),
 * )
 */
class SocialPostListBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $title = $this->configuration['title'];
    $description = $this->configuration['description'];
    $posts = [];
    $posts = views_embed_view('social_posts_view', 'social_posts_block');
    $build = [
      '#theme' => 'social_post_list',
      '#title' => $title,
      '#description' => $description['value'],
      '#posts' => $posts,
    ];
    return $build;
  }
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Block Title'),
      '#description' => $this->t('Block Title'),
      '#default_value' => isset($this->configuration['title']) ? $this->configuration['title'] : '',
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => 10,
    );

    $form['description'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Block Description'),
      '#format' => 'full_html',
      '#description' => $this->t('Block Description'),
      '#default_value' => isset($this->configuration['description']['value']) ? $this->configuration['description']['value'] : '',
      '#weight' => 20,

    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['description'] = $form_state->getValue('description');
  }
}
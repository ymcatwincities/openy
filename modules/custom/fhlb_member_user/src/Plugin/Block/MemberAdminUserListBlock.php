<?php

namespace Drupal\fhlb_member_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Block to display list of member users for current member admin.
 *
 * @Block(
 *   id = "member_admin_user_list_block",
 *   admin_label = @Translation("Member Admin User List Block"),
 *   category = @Translation("FHLB"),
 * )
 */
class MemberAdminUserListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Current Drupal User.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The Entity Type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $users = $this->entityTypeManager->getListBuilder('member_user');
    $content = [];
    $url = Url::fromRoute('entity.member_user.add_form');
    $link_options = [
      'attributes' => [
        'class' => [
          'btn',
          'btn-default',
        ],
      ],
    ];
    $url->setOptions($link_options);
    $header = Link::fromTextAndUrl($this->t('Add Member User'), $url);

    if ($users) {
      $content = $users->render();
    }

    $build = [
      '#theme' => 'member_admin_user_list',
      '#content' => $content,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Vary caching of this block per user.
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

}

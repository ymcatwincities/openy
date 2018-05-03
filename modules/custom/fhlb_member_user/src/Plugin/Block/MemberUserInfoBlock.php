<?php

namespace Drupal\fhlb_member_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Block to display member user information.
 *
 * @Block(
 *   id = "member_user_info_block",
 *   admin_label = @Translation("Member User Info Block"),
 *   category = @Translation("FHLB"),
 * )
 */
class MemberUserInfoBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $build = [];
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser->id());

    // Doesn't apply to Anonymous.
    if ($user->isAnonymous()) {
      return [];
    }

    /** @var \Drupal\fhlb_member_user\Entity\MemberUser $member */
    if ($member = $user->field_fhlb_member_user->entity) {

      $build['name-wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['class' => 'member-name'],
        'name' => [
          '#markup' => '
            <span class="first-name">' . $member->first_name->value . '</span> 
            <span class="last-name">' . $member->last_name->value . '</span>
          ',
        ],
      ];

      $build['logout'] = $this->generateLogout();

      // Member institution doesn't apply to 3rd party members.
      if (!in_array('third_party_user', $user->getRoles())) {

        if ($institution = $member->field_fhlb_mem_institution->entity) {
          $build['inst-wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['class' => 'institution-info'],
            'info' => [
              '#markup' => '
            <span class="institution-name">' . $institution->name->value . '</span> 
            <span class="institution-id">(' . $member->cust_id->value . ')</span>
          ',
            ],
          ];
        }
      }

    }
    // Not a member user.
    else {
      return $this->generateLogout();
    }

    return $build;
  }

  /**
   * Simple helper for the logout render array.
   *
   * @return array
   *   The logout button render array.
   */
  protected function generateLogout() {
    return [
      '#title' => $this->t('Logout'),
      '#type' => 'link',
      '#url' => Url::fromRoute('user.logout'),
      '#attributes' => [
        'class' => ['member-logout'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Vary caching of this block per user.
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

}

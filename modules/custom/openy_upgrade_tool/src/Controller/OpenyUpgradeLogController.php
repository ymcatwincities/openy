<?php

namespace Drupal\openy_upgrade_tool\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface;
use Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OpenyUpgradeLogController.
 *
 *  Returns responses for Openy upgrade log routes.
 */
class OpenyUpgradeLogController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Drupal\openy_upgrade_tool\OpenyUpgradeLogManager definition.
   *
   * @var \Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface
   */
  protected $upgradeLogManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    OpenyUpgradeLogManagerInterface $openy_upgrade_log_manager,
    RendererInterface $renderer,
    DateFormatterInterface $date_formatter
  ) {

    $this->upgradeLogManager = $openy_upgrade_log_manager;
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openy_upgrade_log.manager'),
      $container->get('renderer'),
      $container->get('date.formatter')
    );
  }

  /**
   * Returns Open Y upgrade tool dashboard.
   *
   * @return array
   *   Render array with dashboard.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function dashboard() {
    $build = [];
    $build['content'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['dashboard-wrapper']],
    ];

    $entity_storage = $this->entityTypeManager()
      ->getStorage('openy_upgrade_log');
    $resolved_count = $entity_storage->getQuery()
      ->condition('status', TRUE)
      ->count()
      ->execute();
    $conflicts_count = $entity_storage->getQuery()
      ->condition('status', TRUE, '!=')
      ->count()
      ->execute();

    $total = $resolved_count + $conflicts_count;
    if ($total === 0) {
      $build['content']['message']['#markup'] = $this->t('Congratulation! You have no conflicts detected.');
    }

    $build['content']['conflict_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Conflicts (@count)', [
        '@count' => $conflicts_count,
      ]),
      '#description' => $this->t('Here you can see list of configs that were changed manually and differ from Open Y. In DIFF modal you can approve this changes or restore Open Y version. <br> Also you can resolve conflict and apply both changes(manual and Open Y).'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['conflicts-wrapper']],
    ];
    $build['content']['conflict_wrapper']['view'] = [
      '#type' => 'view',
      '#name' => 'openy_upgrade_dashboard',
      '#display_id' => 'conflicts',
    ];

    if ($resolved_count >= 1) {
      // Show resolved section only if items exist.
      $build['content']['resolved_wrapper'] = [
        '#type' => 'details',
        '#title' => $this->t('Resolved conflicts (@count)', [
          '@count' => $resolved_count,
        ]),
        '#description' => $this->t('Here you can see list of configs that differ from Open Y, but was approved as resolved.'),
        '#attributes' => ['class' => ['resolved-wrapper']],
      ];
      $build['content']['resolved_wrapper']['view'] = [
        '#type' => 'view',
        '#name' => 'openy_upgrade_dashboard',
        '#display_id' => 'resolved',
      ];
    }

    $build['#attached']['library'][] = 'openy_upgrade_tool/dashboard';

    return $build;
  }

  /**
   * Displays a Openy upgrade log  revision.
   *
   * @param int $openy_upgrade_log_revision
   *   The Openy upgrade log  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function revisionShow($openy_upgrade_log_revision) {
    $openy_upgrade_log = $this->entityManager()->getStorage('openy_upgrade_log')->loadRevision($openy_upgrade_log_revision);
    $view_builder = $this->entityManager()->getViewBuilder('openy_upgrade_log');

    return $view_builder->view($openy_upgrade_log);
  }

  /**
   * Page title callback for a Openy upgrade log  revision.
   *
   * @param int $openy_upgrade_log_revision
   *   The Openy upgrade log  revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function revisionPageTitle($openy_upgrade_log_revision) {
    $openy_upgrade_log = $this->entityManager()->getStorage('openy_upgrade_log')->loadRevision($openy_upgrade_log_revision);
    return $this->t('Revision of %title from %date', ['%title' => $openy_upgrade_log->label(), '%date' => \Drupal::service('date.formatter')->format($openy_upgrade_log->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Openy upgrade log .
   *
   * @param \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface $openy_upgrade_log
   *   A Openy upgrade log  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function revisionOverview(OpenyUpgradeLogInterface $openy_upgrade_log) {
    $account = $this->currentUser();
    $langcode = $openy_upgrade_log->language()->getId();
    $langname = $openy_upgrade_log->language()->getName();
    $languages = $openy_upgrade_log->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $openy_upgrade_log_storage = $this->entityManager()->getStorage('openy_upgrade_log');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $openy_upgrade_log->label()]) : $this->t('Revisions for %title', ['%title' => $openy_upgrade_log->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all openy upgrade log revisions") || $account->hasPermission('administer openy upgrade log entities')));
    $delete_permission = (($account->hasPermission("delete all openy upgrade log revisions") || $account->hasPermission('administer openy upgrade log entities')));

    $rows = [];

    $vids = $openy_upgrade_log_storage->revisionIds($openy_upgrade_log);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\openy_upgrade_tool\OpenyUpgradeLogInterface $revision */
      $revision = $openy_upgrade_log_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $openy_upgrade_log->getRevisionId()) {
          $link = $this->l($date, new Url('entity.openy_upgrade_log.revision', ['openy_upgrade_log' => $openy_upgrade_log->id(), 'openy_upgrade_log_revision' => $vid]));
        }
        else {
          $link = $openy_upgrade_log->toLink($date)->toString();;
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.openy_upgrade_log.revision_revert', ['openy_upgrade_log' => $openy_upgrade_log->id(), 'openy_upgrade_log_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.openy_upgrade_log.revision_delete', ['openy_upgrade_log' => $openy_upgrade_log->id(), 'openy_upgrade_log_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['openy_upgrade_log_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}

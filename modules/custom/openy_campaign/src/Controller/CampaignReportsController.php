<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\openy_campaign\CampaignScorecardService;
use Drupal\openy_session_instance\SessionInstanceManager;
use League\Csv\Writer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CampaignReportsController.
 */
class CampaignReportsController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The SessionInstanceManager.
   *
   * @var \Drupal\openy_session_instance\SessionInstanceManagerInterface
   */
  protected $sessionInstanceManager;

  /**
   * @var \Drupal\openy_campaign\CampaignScorecardService
   */
  protected $campaignScorecardService;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The CampaignReportsController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\openy_session_instance\SessionInstanceManager $sessionInstanceManager
   * @param \Drupal\openy_campaign\CampaignScorecardService $campaignScorecardService
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(
    FormBuilder $formBuilder,
    Connection $connection,
    EntityTypeManagerInterface $entityTypeManager,
    SessionInstanceManager $sessionInstanceManager,
    CampaignScorecardService $campaignScorecardService,
    RendererInterface $renderer
  ) {
    $this->formBuilder = $formBuilder;
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->sessionInstanceManager = $sessionInstanceManager;
    $this->campaignScorecardService = $campaignScorecardService;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('session_instance.manager'),
      $container->get('openy_campaign.generate_campaign_scorecard'),
      $container->get('renderer')
    );
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array Render array
   */
  public function showSummary(NodeInterface $node) {
    // Collect information about members and their activities.
    $summary = $this->getSummary($node);

    // Render the information.
    $build = [];

    $build['members'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Members information'),
    ];

    $build['members']['totalMembers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Total amount of members'),
      '#value' => $summary['totalMembers'],
      '#attributes' => ['readonly' => 'readonly'],
      '#suffix' => $this->t('This value should be configured on the %link page',
        ['%link' => Link::createFromRoute($this->t('settings'), 'openy_campaign.settings')->toString()])
    ];

    $build['members']['registeredMembers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registered members by Campaign'),
      '#value' => $summary['registeredMembers'],
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $build['members']['percentageMembers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Percentage of the registered members'),
      '#value' => $summary['percentageMembers'] . '%',
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $build['members']['generateCsv'] = [
      '#title' => $this->t('Generate Members CSV'),
      '#type' => 'link',
      '#url' => Url::fromRoute('openy_campaign.campaign_reports_summary_members_csv', ['node' => $node->id()]),
    ];

    $build['activities'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Activities information'),
    ];

    $build['activities']['totalActivities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Total amount of checked activities'),
      '#value' => $summary['activities']['totalActivities'],
      '#attributes' => ['readonly' => 'readonly'],
    ];

    foreach ($summary['activities']['categories'] as $activity) {
      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = $activity['term'];

      $build['activities']['category_' . $term->id()]['subcategories'] = [
        '#type' => 'details',
        '#title' => $term->getName() . ' (' . $activity['amount'] . ')',
      ];
      $subcategoriesForm = &$build['activities']['category_' . $term->id()]['subcategories'];

      foreach ($activity['subcategories'] as $subcategory) {
        /** @var \Drupal\taxonomy\Entity\Term $subTerm */
        $subTerm = $subcategory['term'];
        $subcategoriesForm['subcategory_' . $subTerm->id()] = [
          '#type' => 'textfield',
          '#title' => $subTerm->getName(),
          '#value' => $subcategory['amount'],
          '#attributes' => ['readonly' => 'readonly'],
        ];
      }
    }

    $build['activities']['generateCsv'] = [
      '#title' => $this->t('Generate Activities CSV'),
      '#type' => 'link',
      '#url' => Url::fromRoute('openy_campaign.campaign_reports_summary_activities_csv', ['node' => $node->id()]),
    ];

    return $build;
  }

  /**
   * Generate Summary Members CSV.
   *
   * @param \Drupal\node\NodeInterface|int $node
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function generateSummaryMembersCsv($node) {
    $summary = $this->getSummary($node);

    $header = [
      $this->t('Title'),
      $this->t('Value'),
    ];

    $records = [
      [$this->t('Total amount of members'), $summary['totalMembers']],
      [$this->t('Registered members by Campaign'), $summary['registeredMembers']],
      [$this->t('Percentage of the registered members'), $summary['percentageMembers'] . '%'],
    ];
    $fileName = 'summary_members_' . date('Y-m-d') . '.csv';

    return $this->createCsvResponse($fileName, $header, $records);
  }

  /**
   * Generate Summary Activities CSV.
   *
   * @param \Drupal\node\NodeInterface|int $node
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function generateSummaryActivitiesCsv($node) {
    $summary = $this->getSummary($node);

    $header = [
      $this->t('Category'),
      $this->t('Subcategory'),
      $this->t('Category Amount'),
      $this->t('Subcategory Amount'),
    ];

    $records = [];

    foreach ($summary['activities']['categories'] as $activity) {
      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = $activity['term'];

      $records[] = [$term->getName(), '', $activity['amount'], ''];

      foreach ($activity['subcategories'] as $subcategory) {
        /** @var \Drupal\taxonomy\Entity\Term $subTerm */
        $subTerm = $subcategory['term'];

        $records[] = ['', $subTerm->getName(), '', $subcategory['amount']];
      }
    }

    $records[] = ['', '', '', ''];
    $records[] = [$this->t('Total'), '', '', $summary['activities']['totalActivities']];

    $fileName = 'summary_activities_' . date('Y-m-d') . '.csv';

    return $this->createCsvResponse($fileName, $header, $records);
  }

  /**
   * Get summary by the node.
   *
   * @param \Drupal\node\NodeInterface|int $node
   *
   * @return array Render array
   */
  private function getSummary($node) {
    if (!($node instanceof NodeInterface)) {
      $node = $this->entityTypeManager->getStorage('node')->load($node);
    }

    $registeredMembers = $this->calculateRegisteredMembers($node);
    $totalMembers = $this->calculateAllMembers();
    $percentageMembers = 0;
    if ($totalMembers > 0) {
      $percentageMembers = round($registeredMembers / $totalMembers * 100, 2);
    }

    $activities = $this->calculateTrackedActivities($node);

    return [
      'registeredMembers' => $registeredMembers,
      'totalMembers' => $totalMembers,
      'percentageMembers' => $percentageMembers,
      'activities' => $activities,
    ];
  }

  /**
   * Render activities report.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array Render array
   */
  public function showActivities(NodeInterface $node) {
    $build = [
      'view' => views_embed_view('campaign_activities_report', 'campaign_activities_report_page', $node->id()),
    ];

    return $build;
  }

  /**
   * Get an amount of the registered members.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return int Amount of members.
   */
  private function calculateRegisteredMembers(NodeInterface $node) {
    // Fetch all members from the current campaign.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('openy_campaign_member_campaign', 'mc');
    $query->addField('mc', 'id');
    $query->condition('mc.campaign', $node->id());
    $query = $query->countQuery();
    $amount = $query->execute()->fetchField();

    return (int) $amount;
  }

  /**
   * Get an amount of total members in the system.
   *
   * @return int Amount of members.
   */
  private function calculateAllMembers() {
    // Fetch the value from the Personify or from the settings.
    $config = $this->config('openy_campaign.general_settings');
    $amount = $config->get('total_amount_of_visitors');

    return (int) $amount;
  }

  /**
   * Get information about the tracked activities.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array Full information about the activities.
   */
  private function calculateTrackedActivities(NodeInterface $node) {
    // Fetch all members from the current campaign.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('openy_campaign_memb_camp_actv', 'mca');
    $query->addField('mca', 'id', 'mca_id');
    $query->addField('mca', 'activity', 'mca_activity');
    $query->join('openy_campaign_member_campaign', 'mc', 'mc.id = mca.member_campaign');
    $query->addField('mc', 'id');
    $query->condition('mc.campaign', $node->id());

    $activities = $query->execute()->fetchAll();
    $data = [];

    $data['totalActivities'] = count($activities);
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

    foreach ($activities as $activity) {
      // Fetch subcategory.
      $subcategory = $termStorage->load($activity->mca_activity);
      // In case if some categories have been removed during the challenge, we need to skip it from the calculation.
      if (empty($subcategory)) {
        --$data['totalActivities'];
        continue;
      }

      // Fetch category.
      $ancestors = $termStorage->loadAllParents($subcategory->id());
      $ancestors = array_reverse($ancestors);
      /** @var \Drupal\taxonomy\Entity\Term $category */
      $category = reset($ancestors);

      // Calculate amount of activities in the category.
      $categories = &$data['categories'][$category->id()];
      if (!isset($categories['amount'])) {
        $categories['amount'] = 1;
      }
      else {
        ++$categories['amount'];
      }

      $categories['term'] = $category;

      // Calculate amount of activities in the subcategory.
      $subcategories = &$data['categories'][$category->id()]['subcategories'];
      if (!isset($subcategories[$subcategory->id()]['amount'])) {
        $subcategories[$subcategory->id()]['amount'] = 1;
      }
      else {
        ++$subcategories[$subcategory->id()]['amount'];
      }
      $subcategories[$subcategory->id()]['term'] = $subcategory;

    }

    return $data;
  }

  /**
   * Generate CSV file.
   *
   * @param $fileName
   * @param $header
   * @param $records
   * @return \Symfony\Component\HttpFoundation\Response
   */
  private function createCsvResponse($fileName, $header, $records) {
    $csv = Writer::createFromString('');

    $csv->insertOne($header);
    $csv->insertAll($records);

    $response = new Response();
    $response->setContent($csv);

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

    return $response;
  }

  /**
   * Live Scoreboard controller.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array|bool
   */
  public function generateLiveScorecard(Node $node) {
    $result = $this->campaignScorecardService->generateLiveScorecard($node);
    return $result;
  }

  /**
   * AJAX callback for the different campaigns.
   */
  public function ajaxCallbackGenerateLiveScorecard($node) {
    $node = $this->entityTypeManager->getStorage('node')->load($node);
    $result = $this->campaignScorecardService->generateLiveScorecard($node);
    return new Response($this->renderer->render($result));
  }

}

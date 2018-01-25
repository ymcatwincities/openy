<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\openy_campaign\Entity\CampaignUtilizationActivitiy;
use Drupal\openy_campaign\OpenYLocaleDate;
use Drupal\openy_campaign\RegularUpdater;
use Drupal\openy_popups\Form\ClassBranchesForm;
use Drupal\openy_session_instance\SessionInstanceManager;
use Drupal\taxonomy\Entity\Term;
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
   * @var FormBuilder
   */
  protected $formBuilder;

  /**
   * The Database service.
   *
   * @var Connection
   */
  protected $connection;

  /**
   * The entity type manager service.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The SessionInstanceManager.
   *
   * @var \Drupal\openy_session_instance\SessionInstanceManagerInterface
   */
  protected $sessionInstanceManager;

  /**
   * The CampaignReportsController constructor.
   *
   * @param FormBuilder $formBuilder
   *   The form builder.
   * @param Connection $connection
   *   The database connection service.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param SessionInstanceManager $session_instance_manager
   *   The SessionInstanceManager.
   */
  public function __construct(FormBuilder $formBuilder, Connection $connection, EntityTypeManagerInterface $entityTypeManager, SessionInstanceManager $sessionInstanceManager) {
    $this->formBuilder = $formBuilder;
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->sessionInstanceManager = $sessionInstanceManager;
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
      $container->get('session_instance.manager')
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
      /** @var Term $term */
      $term = $activity['term'];

      $build['activities']['category_' . $term->id()]['subcategories'] = [
        '#type' => 'details',
        '#title' => $term->getName() . ' (' . $activity['amount'] . ')',
      ];
      $subcategoriesForm = &$build['activities']['category_' . $term->id()]['subcategories'];

      foreach ($activity['subcategories'] as $subcategory) {
        /** @var Term $subTerm */
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
      /** @var Term $term */
      $term = $activity['term'];

      $records[] = [$term->getName(), '', $activity['amount'], ''];

      foreach ($activity['subcategories'] as $subcategory) {
        /** @var Term $subTerm */
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
   * @param \Drupal\node\NodeInterface|int $node
   *
   * @return array Render array
   */
  private function getSummary($node) {
    if (!($node instanceof NodeInterface)) {
      $node = Node::load($node);
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
   * Get an amount of registered members.
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

    return (int)$amount;
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

    return (int)$amount;
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

    foreach ($activities as $activity) {
      // Fetch subcategory.
      $subcategory = Term::load($activity->mca_activity);
      // In case if some categories have been removed during the challenge,
      // we need to skip it from the calculation.
      if (empty($subcategory)) {
        --$data['totalActivities'];
        continue;
      }

      // Fetch category.
      $ancestors = $this->entityTypeManager->getStorage("taxonomy_term")->loadAllParents($subcategory->id());
      $ancestors = array_reverse($ancestors);
      /** @var Term $category */
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
   * @param $fileName
   * @param $header
   * @param $records
   * @return Response
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
   * * Live Scoreboard controller.
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array|bool
   */
  public function generateLiveScorecard(NodeInterface $node) {
    if ($node->bundle() != 'campaign') {
      return FALSE;
    }

    // Prepare dates of campaign.
    $campaignTimezone = new \DateTime($node->get('field_campaign_timezone')->getString());
    $campaignTimezone = $campaignTimezone->getTimezone();

    // All time of campaign.
    $localeCampaignStart = OpenYLocaleDate::createDateFromFormat($node->get('field_campaign_start_date')->getString());
    $localeCampaignStart->convertTimezone($campaignTimezone);

    $localeCampaignEnd = OpenYLocaleDate::createDateFromFormat($node->get('field_campaign_end_date')->getString());
    $localeCampaignEnd->convertTimezone($campaignTimezone);

    // Time of early registration.
    $localeRegistrationStart = OpenYLocaleDate::createDateFromFormat($node->get('field_campaign_reg_start_date')->getString());
    $localeRegistrationStart->convertTimezone($campaignTimezone);

    $localeRegistrationEnd = OpenYLocaleDate::createDateFromFormat($node->get('field_campaign_reg_end_date')->getString());
    $localeRegistrationEnd->convertTimezone($campaignTimezone);

    $result['dates']['registration_start'] = $localeRegistrationStart->getDate()->format('m/d');
    $result['dates']['registration_end'] = $localeRegistrationEnd->getDate()->format('m/d');
    $result['dates']['campaign_start'] = $localeCampaignStart->getDate()->format('m/d');
    $result['dates']['campaign_end'] = $localeCampaignEnd->getDate()->format('m/d');

    // Current dates. + labels with dates.
    $localTime = OpenYLocaleDate::createDateFromFormat(date('c'));
    $localTime->convertTimezone($campaignTimezone);

    if ($localTime > $localeRegistrationEnd) {
      $actual_early_date = $localeRegistrationEnd->getDate()->format('m/d') . ' at ' . $localeRegistrationEnd->getDate()->format('h:ia');
    }
    else {
      $actual_early_date = $localTime->getDate()->format('m/d');
    }

    if ($localTime > $localeCampaignEnd) {
      $actual_campaign_date = $localeCampaignEnd->getDate()->format('m/d') . ' at ' . $localeCampaignEnd->getDate()->format('h:ia');
    }
    else {
      $actual_campaign_date = $localTime->getDate()->format('m/d');
    }

    $result['dates']['actual_date'] = $localTime->getDate()->format('m/d');
    $result['dates']['actual_early_date'] = $actual_early_date;
    $result['dates']['actual_campaign_date'] = $actual_campaign_date;

    // Get branches for current campaign.
    // @todo get data from new widget. Get Branches and TARGET.
    $branches = $this->getCampaignBranches($node);
    $branchIds = [];
    foreach ($branches as $branch) {
      $branchIds[] = $branch->branch;
    }

    // Calculate registered members by branch, Early registration and During all campain.
    $earlyActualMembers = $this->calculateRegisteredMembersByBranch($node, $branchIds, $localeRegistrationStart->getTimestamp(), $localeRegistrationEnd->getTimestamp());
    $challengeRegistrationOfMembers = $this->calculateRegisteredMembersByBranch($node, $branchIds, $localeRegistrationStart->getTimestamp(), $localeCampaignEnd->getTimestamp());

    // @todo remove fake Targets generation.
    $targets = $this->getTargets($branches, $node);

    // Prepare render array.
    $result['registration']['early'] = [];
    $result['registration']['challenge'] = [];
    $result['utilization'] = [];

    $register_goal = $node->get('field_campaign_registration_goal')->getValue();
    $utilization_goal = $node->get('field_campaign_utilization_goal')->getValue();
    $registerGoal = !empty($register_goal) ? $register_goal[0]['value'] : 5;
    $utilizationGoal = !empty($utilization_goal) ? $utilization_goal[0]['value'] : 45;
    $result['goals']['registration_goal'] = $registerGoal;
    $result['goals']['utilization_goal'] = $utilizationGoal;

    foreach ($branches as $id => $branch) {
      $result['branches'][$id]['nid'] = $branch->personify_branch;
      $result['branches'][$id]['title'] = $branch->title;
      $target = $targets[$id];
      $result['branches'][$id]['target'] = $target;

      // Early registration calculation
      $goal = number_format($target * $registerGoal/100);
      $result['registration']['early'][$id]['registration_goal'] = $goal;

      $actual = $earlyActualMembers[$id]->count_id;
      $result['registration']['early'][$id]['actual'] = $actual;

      $of_members = number_format($actual/$targets[$id] * 100, 1);
      $result['registration']['early'][$id]['of_members'] = $of_members;

      $of_goal = number_format($actual/$goal * 100, 1);
      $result['registration']['early'][$id]['of_goal'] = $of_goal;

      // Total Early registration calculation
      $result['total']['target'] += $target;
      $result['total']['registration_goal'] += $goal;
      $result['total']['actual'] += $actual;
      $result['total']['of_members'] += $of_members;
      $result['total']['of_goal'] += $of_goal;

      // Challenge registration calculation
      $result['registration']['challenge'][$id]['registration_goal'] = $goal;

      $reg_actual = $challengeRegistrationOfMembers[$id]->count_id;
      $result['registration']['challenge'][$id]['actual'] = $reg_actual;

      $reg_of_member = number_format($reg_actual/$target * 100, 1);
      $result['registration']['challenge'][$id]['of_members'] = $reg_of_member;

      $reg_of_goal = number_format($reg_actual/$goal * 100, 1);
      $result['registration']['challenge'][$id]['of_goal'] = $reg_of_goal;

      $result['total']['reg_registration_goal'] += $goal;
      $result['total']['reg_actual'] += $reg_actual;
      $result['total']['reg_of_members'] += $reg_of_member;
      $result['total']['reg_of_goal'] += $reg_of_goal;

      // Utilization calculation
      $util_goal = number_format($goal * $utilizationGoal/100);
      $result['utilization'][$id]['goal']  = $util_goal;

      $util_actual = rand(1, $util_goal);
      $result['utilization'][$id]['actual'] = $util_actual;

      $util_of_member = number_format($util_actual/$reg_actual * 100,1);
      $result['utilization'][$id]['of_members'] = $util_of_member;

      $util_of_goal = number_format($util_actual/$util_goal * 100, 1);
      $result['utilization'][$id]['of_goal'] = $util_of_goal;

      //Utilization total
      $result['total']['util_registration_goal'] += $util_goal;
      $result['total']['util_actual'] += $util_actual;
      $result['total']['util_of_members'] += $util_of_member;
      $result['total']['util_of_goal'] += $util_of_goal;

    }

    $result['total']['of_members'] = number_format($result['total']['of_members']/count($targets), 1);
    $result['total']['of_goal'] = number_format($result['total']['of_goal']/count($targets), 1);
    $result['total']['reg_of_members'] = number_format($result['total']['reg_of_members']/count($targets), 1);
    $result['total']['reg_of_goal'] = number_format($result['total']['reg_of_goal']/count($targets), 1);
    $result['total']['util_of_members'] = number_format($result['total']['util_of_members']/count($targets), 1);
    $result['total']['util_of_goal'] = number_format($result['total']['util_of_goal']/count($targets), 1);

    $build = [
      '#theme' => 'openy_campaign_scorecard',
      '#result' => $result
    ];

    return $build;
  }

  /**
   * Fake generation of targets.
   *
   * @param $branches_list
   * @param $campaign
   *
   * @return array
   */
  public function getTargets($branches_list, $campaign) {
    $targets = [];
    $cid = 'openy_campaign:branch_targets:' . $campaign->id();

    if ($cache = \Drupal::cache()->get($cid)) {
      $targets = $cache->data;
    }
    else {
      foreach ($branches_list as $branch_id => $branch) {
        $targets[$branch_id] = rand(1000, 10000);
      }
      \Drupal::cache()->set($cid, $targets);
    }

    return $targets;
  }

  /**
   * Get selected Branches from current campaign.
   *
   * @param $node
   */
  public function getCampaignBranches($node) {
    $branchIds = $node->get('field_campaign_branches')->getValue();
    foreach ($branchIds as $branchId) {
      $ids[] = $branchId['target_id'];
    }

    $connection  = \Drupal::database();
    $query = $connection->select('openy_campaign_mapping_branch', 'b');
    $query->fields('b', ['personify_branch', 'branch']);
    $query->join('node_field_data', 'n', 'n.nid = b.branch');
    $query->fields('n', ['title']);
    $query->condition('b.branch', $ids, 'IN');

    $result = $query->execute()->fetchAllAssoc('branch');

    return $result;
  }


  /**
   * Get Count of registered users by branches for campaign.
   *
   * @param $node
   * @param $branches
   * @param $dateStart
   * @param $dateEnd
   *
   * @return mixed
   */
  public function calculateRegisteredMembersByBranch($node, $branches, $dateStart, $dateEnd) {
    $connection = \Drupal::database();
    $query = $connection->select('openy_campaign_member', 'cm');
    $query->leftJoin('openy_campaign_member_campaign', 'mc', 'mc.member = cm.id');
    $query->addExpression('COUNT(cm.id)', 'count_id');
    $query->fields('cm', array('branch'));
    $query->condition('cm.branch', $branches, 'IN');
    $query->condition('mc.campaign', $node->id(), '=');
    $query->condition('mc.created', [$dateStart, $dateEnd], 'BETWEEN');
    $query->groupBy('cm.branch');

    $result = $query->execute()->fetchAllAssoc('branch');
    return $result;
  }
}

<?php

namespace Drupal\openy_campaign\Plugin\views\field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberCheckin;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checkins handler for the member entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("member_campaign_checkins")
 */
class MemberCampaignCheckins extends FieldPluginBase {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a EntityLabel object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\openy_campaign\Entity\MemberCampaign $entity */
    if ($values->_entity instanceof MemberCampaign) {
      $entity = $values->_entity;
    }
    else {
      $relationship_entities = $values->_relationship_entities;
      $entity = $relationship_entities['member_campaign'];
    }

    /** @var \Drupal\openy_campaign\Entity\Member $member */
    $member = $entity->getMember();

    if (empty($member)) {
      return 0;
    }

    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $entity->getCampaign();

    // Get site timezone.
    $config = $this->configFactory->get('system.date');
    $configSiteDefaultTimezone = !empty($config->get('timezone.default')) ? $config->get('timezone.default') : date_default_timezone_get();
    $siteDefaultTimezone = new \DateTimeZone($configSiteDefaultTimezone);

    /** @var \DateTime $start */
    $start = $campaign->field_campaign_start_date->date;
    // Reset time to include the current day to the list.
    $start->setTimezone($siteDefaultTimezone);
    $start->setTime(0, 0, 0);

    /** @var \DateTime $end */
    $end = $campaign->field_campaign_end_date->date;
    $end->setTimezone($siteDefaultTimezone);

    $facilityCheckInIds = MemberCheckin::getFacilityCheckIns($member->id(), $start, $end);

    return count($facilityCheckInIds);

  }

}

<?php

namespace Drupal\openy_campaign;

/**
 * CRMClientInterface
 */
interface CRMClientInterface {

  /**
   * @param $facilityId
   */
  public function getMemberInformation($facilityId);

  /**
   * @param $masterId
   * @param \DateTime $dateFrom
   * @param \DateTime $dateTo
   */
  public function getVisitCountByDate($masterId, \DateTime $dateFrom, \DateTime $dateTo);

  /**
   * @param array $listIds
   * @param \DateTime $dateFrom
   * @param \DateTime $dateTo
   */
  public function getVisitsBatch(array $listIds, \DateTime $dateFrom, \DateTime $dateTo);

}

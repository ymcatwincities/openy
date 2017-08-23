<?php

namespace Drupal\openy_campaign;


interface CRMClientInterface {

  public function getMemberInformation($facilityId);

  public function getVisitCountByDate($masterId, \DateTime $dateFrom, \DateTime $dateTo);

  public function getVisitsBatch(array $listIds, \DateTime $dateFrom, \DateTime $dateTo);

}

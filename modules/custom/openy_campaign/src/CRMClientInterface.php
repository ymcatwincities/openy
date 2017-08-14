<?php

namespace Drupal\openy_campaign;


interface CRMClientInterface {

  public function getMemberInformation($facility_id);

  public function getVisitCountByDate($master_id, \DateTime $date_from, \DateTime $date_to);

  public function getVisitsBatch(array $list_ids, \DateTime $date_from, \DateTime $date_to);

}

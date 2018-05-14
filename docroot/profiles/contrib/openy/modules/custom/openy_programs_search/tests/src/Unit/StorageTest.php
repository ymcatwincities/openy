<?php

namespace Drupal\Tests\openy_programs_search\Unit;

/**
 * Class StorageTest.
 */
class StorageTest extends \PHPUnit_Framework_TestCase {

  /**
   * Backup globals.
   *
   * @var bool
   *
   * @see https://github.com/sebastianbergmann/phpunit/issues/451
   * @see https://github.com/silverstripe/silverstripe-behat-extension/commit/7ef575c961ef8a42646b9a30d5a37ad125290dce
   */
  protected $backupGlobals = FALSE;

  /**
   * Test getCategories().
   */
  public function testGetCategories() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $categories = $storage->getCategories();
    $this->assertArrayHasKey(2016, $categories);
  }

  /**
   * Test getProgramsByBranchAndCategory().
   */
  public function testGetProgramsByBranchAndCategory() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $programs = $storage->getProgramsByBranchAndCategory(91, "2016");
    $this->assertEquals(1, count($programs));
  }

  /**
   * Test getMapCategoriesByBranch().
   */
  public function testGetMapCategoriesByBranch() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $map = $storage->getCategoriesByBranch(91);
    $this->assertArrayHasKey(2016, $map);
  }

  /**
   * Test getChildCareRegistrationLink().
   */
  public function testGetChildCareRegistrationLink() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $link = $storage->getChildCareRegistrationLink(426, 8595, 14196);
    $expected = 'https://operations.daxko.com/Online/4003/Programs/ChildCareSearch.mvc/details?program_id=8595&location_id=426&location_type_id=2&context_id=14196';
    $this->assertEquals(md5($expected), md5($link));
  }

  /**
   * Test getChildProgramRateOption().
   */
  public function testGetChildCareProgramRateOptions() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $rates = $storage->getChildCareProgramRateOptions(422, 8595);
    $this->assertArrayHasKey(14196, $rates);
  }

  /**
   * Test getChildCareProgramsBySchool().
   */
  public function testGetChildCareProgramsBySchool() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $programs = $storage->getChildCareProgramsBySchool(426);
    $this->assertArrayHasKey(8595, $programs);
  }

  /**
   * Test getSchoolsByLocation().
   */
  public function testGetSchoolsByLocation() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $schools = $storage->getSchoolsByLocation(106);
    $this->assertArrayHasKey(641, $schools);
  }

  /**
   * Test getChildCareProgramsByLocation().
   */
  public function testGetChildCareProgramsByLocation() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $programs = $storage->getChildCareProgramsByLocation(106);
    $this->assertArrayHasKey(4111, $programs);
  }

  /**
   * Test getSchoolsByChildCareProgramId().
   */
  public function testGetSchoolsByChildCareProgramId() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $schools = $storage->getSchoolsByChildCareProgramId(8595);
    $this->assertArrayHasKey(426, $schools);
  }

  /**
   * Test getLocationsByChildCareProgramId().
   */
  public function testGetLocationsByChildCareProgramId() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $locations = $storage->getLocationsByChildCareProgramId(9532);
    $this->assertArrayHasKey(123, $locations);
  }

  /**
   * Test getSessionsByProgramAndLocation().
   */
  public function testGetSessionsByProgramAndLocation() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $sessions = $storage->getSessionsByProgramAndLocation(8415, 106);

    $ids = [];
    foreach ($sessions as $session) {
      $ids[$session->id] = $session->id;
    }

    $this->assertArrayHasKey(300690, $ids);
  }

  /**
   * Test getProgramsByLocation().
   */
  public function testGetProgramsByLocation() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $programs = $storage->getProgramsByLocation(106);

    $ids = [];
    foreach ($programs as $program) {
      $ids[$program->id] = $program->id;
    }

    $this->assertArrayHasKey(8385, $ids);
  }

  /**
   * Test getRegistrationLinkByProgram().
   */
  public function testGetRegistrationLink() {
    $storage = \Drupal::service('openy_programs_search.data_storage');
    $link = $storage->getRegistrationLink(9880, 328097);
    $expected = 'https://operations.daxko.com/Online/4003/Programs/Search.mvc/details?program_id=9880&session_ids=328097';
    $this->assertEquals(md5($expected), md5($link));
  }

}

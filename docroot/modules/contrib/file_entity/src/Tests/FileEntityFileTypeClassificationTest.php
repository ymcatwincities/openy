<?php

/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityFileTypeClassificationTest.
 */

namespace Drupal\file_entity\Tests;

use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;

/**
 * Test existing file entity classification functionality.
 *
 * @group file_entity
 */
class FileEntityFileTypeClassificationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file');

  /**
   * Disable strict schema checking until schema is updated.
   *
   * @todo Update schema and remove this.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Get the file type of a given file.
   *
   * @param $file
   *   A file object.
   *
   * @return
   *   The file's file type as a string.
   */
  function getFileType($file) {
    $type = db_select('file_managed', 'fm')
      ->fields('fm', array('type'))
      ->condition('fid', $file->id(), '=')
      ->execute()
      ->fetchAssoc();

    return $type;
  }

  /**
   * Test that existing files are properly classified by file type.
   */
  function testFileTypeClassification() {
    // Get test text and image files.
    $file = current($this->drupalGetTestFiles('text'));
    $text_file = File::create((array) $file);
    $text_file->save();
    $file = current($this->drupalGetTestFiles('image'));
    $image_file = File::create((array) $file);
    $image_file->save();

    // Enable file entity which adds adds a file type property to files and
    // queues up existing files for classification.
    \Drupal::service('module_installer')->install(array('file_entity'));

    // Existing files have yet to be classified and should have an undefined
    // file type.
    $file_type = $this->getFileType($text_file);
    $this->assertEqual($file_type['type'], 'undefined', t('The text file has an undefined file type.'));
    $file_type = $this->getFileType($image_file);
    $this->assertEqual($file_type['type'], 'undefined', t('The image file has an undefined file type.'));

    // The classification queue is processed during cron runs. Run cron to
    // trigger the classification process.
    $this->cronRun();

    // The classification process should assign a file type to any file whose
    // MIME type is assigned to a file type. Check to see if each file was
    // assigned a proper file type.
    $file_type = $this->getFileType($text_file);
    $this->assertEqual($file_type['type'], 'document', t('The text file was properly assigned the Document file type.'));
    $file_type = $this->getFileType($image_file);
    $this->assertEqual($file_type['type'], 'image', t('The image file was properly assigned the Image file type.'));
  }
}

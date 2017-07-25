<?php

namespace Drupal\webform_devel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform_devel\WebformDevelSchemaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Provides route responses for webform devel schema.
 */
class WebformDevelSchemaController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform devel schema generator
   *
   * @var \Drupal\webform_devel\WebformDevelSchemaInterface
   */
  protected $schema;

  /**
   * Constructs a WebformDevelSchemaController object.
   *
   * @param \Drupal\webform_devel\WebformDevelSchemaInterface $schema
   *   The webform devel schema generator
   */
  public function __construct(WebformDevelSchemaInterface $schema) {
    $this->schema = $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform_devel.schema')
    );
  }

  /**
   * Returns a webform's schema as a CSV.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform to be exported.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   A streamed response containing webform's schema as a CSV.
   */
  public function index(WebformInterface $webform) {
    // From: http://obtao.com/blog/2013/12/export-data-to-a-csv-file-with-symfony/
    $response = new StreamedResponse(function () use ($webform) {
      $handle = fopen('php://output', 'r+');

      // Header.
      fputcsv($handle, $this->schema->getColumns());

      // Rows.
      $elements = $this->schema->getElements($webform);
      foreach ($elements as $element) {
        fputcsv($handle, $element);
      }

      fclose($handle);
    });
    $response->headers->set('Content-Type', 'application/force-download');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $webform->id() . '.schema.csv"');
    return $response;
  }

}

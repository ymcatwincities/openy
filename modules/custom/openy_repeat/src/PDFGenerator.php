<?php

namespace Drupal\openy_repeat;

/**
 * Class PDFGenerator.
 *
 * @package Drupal\openy_repeat
 */
class PDFGenerator {

  /**
   * Outputs generated PDF.
   *
   * @param array $settings
   *   An array which contains content for pdf's body.
   */
  public function generatePdf($settings) {
    if (empty($settings)) {
      return;
    }
    if ($temporary_directory = file_directory_temp()) {
      define("_MPDF_TEMP_PATH", $temporary_directory);
    }
    $html = mb_convert_encoding(render($settings['body']), 'UTF-8', 'UTF-8');
    $mpdf = new \Mpdf\Mpdf();
    $stylesheet = file_get_contents(drupal_get_path('module', 'openy_repeat') . '/css/print_pdf.css');
    $mpdf->SetHTMLFooter(render($settings['footer']));
    $mpdf->WriteHTML($stylesheet, 1);
    $mpdf->WriteHTML($html, 2);
    $mpdf->Output('schedules.pdf', 'I');
    exit();
  }

}

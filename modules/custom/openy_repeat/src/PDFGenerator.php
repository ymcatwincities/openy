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
    $temporary_directory = file_directory_temp();
    define("_MPDF_TEMP_PATH", $temporary_directory);
    ini_set("pcre.backtrack_limit", "5000000");
    $html = mb_convert_encoding(render($settings['body']), 'UTF-8', 'UTF-8');
    $mpdf = new \Mpdf\Mpdf(['format' => 'A4-L', 'tempDir' => $temporary_directory]);
    $stylesheet = file_get_contents(drupal_get_path('module', 'openy_repeat') . '/css/print_pdf.css');
    $mpdf->SetTitle($settings['title']);
    $mpdf->SetHTMLFooter(render($settings['footer']));
    $mpdf->WriteHTML($stylesheet, 1);
    $mpdf->WriteHTML($html, 2);
    $mpdf->Output('schedules.pdf', 'I');
    exit();
  }

}

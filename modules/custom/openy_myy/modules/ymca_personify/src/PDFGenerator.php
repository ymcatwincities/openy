<?php

namespace Drupal\ymca_personify;

/**
 * Class PDFGenerator.
 *
 * @package Drupal\ymca_personify
 */
class PDFGenerator {

  /**
   * Outputs generated PDF.
   *
   * @param array $settings
   *   An array which contains content for pdf's body and footer.
   */
  public function generatePdf($settings) {
    if (empty($settings)) {
      return;
    }
    if ($temporary_directory = file_directory_temp()) {
      define("_MPDF_TEMP_PATH", $temporary_directory);
    }
    $html = mb_convert_encoding(render($settings['body']), 'UTF-8', 'UTF-8');
    require_once DRUPAL_ROOT . '/libraries/MPDF57/mpdf.php';
    $mpdf = new \mPDF('', 'A4', '', '', 10, 10, 14, 10, 10, 5);
    $stylesheet = file_get_contents(DRUPAL_ROOT . '/modules/custom/ymca_personify/css/print.css');
    $mpdf->SetHTMLFooter(render($settings['footer']));
    $mpdf->WriteHTML($stylesheet, 1);
    $mpdf->WriteHTML($html, 2);
    $mpdf->Output('childcare_payment_history.pdf', 'I');
    exit();
  }

}

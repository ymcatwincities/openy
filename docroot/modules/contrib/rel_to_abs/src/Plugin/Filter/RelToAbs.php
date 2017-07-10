<?php

namespace Drupal\rel_to_abs\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to convert relative paths to absolute URLs.
 *
 * @Filter(
 *   id = "rel_to_abs",
 *   title = @Translation("Convert relative paths to absolute URLs"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class RelToAbs extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    $base_url = \Drupal::url('<front>', array(), array(
      'absolute' => TRUE,
      'language' => \Drupal::getContainer()
        ->get('language_manager')
        ->getLanguage($langcode),
    ));

    $text = $this->absoluteUrl($text, $base_url);
    return new FilterProcessResult($text);

  }

  /**
   * Absolute url callback.
   *
   * @param string $txt
   *   Text to be parsed.
   * @param string $base_url
   *   Base url of the site to prefix.
   *
   * @return string
   *   Processed text.
   */
  public function absoluteUrl($txt, $base_url) {
    $needles = array('href="', 'src="', 'background="');
    $new_txt = '';
    if (substr($base_url, -1) != '/') {
      $base_url .= '/';
    }
    $new_base_url = $base_url;

    // Check if Drupal installed in subdirectory.
    $sub_dir = FALSE;
    $parts = parse_url($new_base_url);
    if ($parts['path'] !== '/') {
      $sub_dir = $parts['path'];
    }

    foreach ($needles as $needle) {
      while ($pos = strpos($txt, $needle)) {
        $pos += strlen($needle);
        if (substr($txt, $pos, 7) != 'http://' && substr($txt, $pos, 8) != 'https://' && substr($txt, $pos, 6) != 'ftp://' && substr($txt, $pos, 7) != 'mailto:' && substr($txt, $pos, 2) != '//' && substr($txt, $pos, 1) != '#' && substr($txt, $pos, 4) != 'tel:') {
          $new_txt .= substr($txt, 0, $pos) . $new_base_url;
        }
        else {
          $new_txt .= substr($txt, 0, $pos);
        }
        $txt = substr($txt, $pos);
        // Remove leading sub-directory prefix if site installed in subdir.
        if ($sub_dir) {
          if (substr($txt, 0, strlen($sub_dir)) == $sub_dir) {
            $txt = substr($txt, strlen($sub_dir));
          }
        }
      }
      $txt = $new_txt . $txt;
      $new_txt = '';
    }
    return $txt;
  }

}

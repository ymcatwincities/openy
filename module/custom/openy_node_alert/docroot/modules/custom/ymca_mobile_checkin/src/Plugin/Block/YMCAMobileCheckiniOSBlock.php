<?php

namespace Drupal\ymca_mobile_checkin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;

/**
 * Provides Mobile Check-in application block for iOS.
 *
 * @Block(
 *   id = "mobile_checkin_block_ios",
 *   admin_label = @Translation("Mobile Check-in Application iOS"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class YMCAMobileCheckiniOSBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $files = [];
    $labels = [
      'plist' => $this->t('Install Check-in app'),
      'ipa' => $this->t('Download ipa file'),
    ];

    if (!empty($config['plist'])) {
      $url = file_create_url($config['plist']);
      $url = preg_replace('/^https?(.*)/', 'https$1', $url);
      $files[] = [
        'url' => 'itms-services://?action=download-manifest&url=' . urlencode($url),
        'label' => $labels['plist'],
      ];
    }

    foreach ($config['ipa'] as $fid) {
      if (!$file = File::load($fid)) {
        continue;
      }
      $uri = $file->getFileUri();
      $url = file_create_url($uri);
      $url = preg_replace('/^https?(.*)/', 'https$1', $url);
      $files[] = [
        'url' => $url,
        'label' => $labels['ipa'],
        'date' => \Drupal::service('date.formatter')->format($file->getChangedTime(), 'custom', 'd F Y'),
      ];
    }

    return [
      '#theme' => 'ymca_mobile_checkin_block',
      '#files' => $files,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['ipa'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('IPA file'),
      '#upload_location' => 'public://ymca_mobile_checkin',
      '#default_value' => $config['ipa'],
      '#upload_validators' => [
        'file_validate_extensions' => ['ipa'],
      ],
      '#required' => TRUE,
      '#field_name' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    /* @var $file_usage DatabaseFileUsageBackend */
    $file_usage = \Drupal::service('file.usage');

    $fids = $form_state->getValue('ipa');
    // Register newly uploaded files.
    foreach (array_diff($fids, $config['ipa']) as $fid) {
      if (!$file = File::load($fid)) {
        continue;
      }
      $file_usage->add($file, 'ymca_mobile_checkin', 'block', $config['uuid']);

      // Create plist file.
      $uri = $file->getFileUri();
      $url = file_create_url($uri);
      $plist = [
        '#theme' => 'ymca_mobile_checkin_plist',
        '#url' => $url,
      ];
      $plist_content = \Drupal::service('renderer')->render($plist);
      $plist_uri = $uri . '.plist';
      if ($plist_path = file_unmanaged_save_data($plist_content, $plist_uri, FILE_EXISTS_REPLACE)) {
        $this->setConfigurationValue('plist', $plist_path);
      }
      else {
        \Drupal::logger('ymca_mobile_checkin')->error('Unable to save the .plist file.');
      }
    }

    // Remove file usage of removed files.
    // TODO: how to remove file usage on page/block removal?
    foreach (array_diff($config['ipa'], $fids) as $fid) {
      if (!$file = File::load($fid)) {
        continue;
      }
      $file_usage->delete($file, 'ymca_mobile_checkin', 'block', $config['uuid']);

      // Remove plist file.
      $uri = $file->getFileUri();
      $plist_uri = $uri . '.plist';
      file_unmanaged_delete($plist_uri);
    }

    $this->setConfigurationValue('ipa', $fids);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ipa' => [],
      'plist' => '',
    ];
  }

}

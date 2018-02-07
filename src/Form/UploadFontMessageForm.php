<?php

namespace Drupal\openy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Defines a form for setting Google Maps API Key during install.
 */
class UploadFontMessageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_upload_font_message';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array &$install_state = NULL) {
    $base_url = $GLOBALS['base_url'];
    $form['#title'] = $this->t('How to upload Cachet fonts');

    $form['updatefont']['markup'] = [
      '#type' => 'markup',
      '#markup' => $this->t('By default free Verdana fonts are used. While paid Cachet fonts are recommended by YMCA Branding Guide. 
To use Cachet fonts on the OpenY website go to the <a href=\'@config_url\'>OpenY Font Settings page</a> and upload the font files there.
<img src="../profiles/contrib/openy/src/Form/uploadfont.png">', ['@config_url' => "$base_url/admin/config/user-interface/fonts"]),
    ];

    $form['actions'] = [
      'continue' => [
        '#type' => 'submit',
        '#value' => $this->t('OK'),
      ],
      '#type' => 'actions',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}

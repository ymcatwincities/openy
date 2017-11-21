<?php

namespace Drupal\webform;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Webform add-ons manager.
 */
class WebformAddonsManager implements WebformAddonsManagerInterface {

  use StringTranslationTrait;

  /**
   * Projects that provides additional functionality to the Webform module.
   *
   * @var array
   */
  protected $projects;

  /**
   * Constructs a WebformAddOnsManager object.
   */
  public function __construct() {
    $this->promotions = $this->initPromotions();
    $this->projects = $this->initProjects();
  }

  /**
   * {@inheritdoc}
   */
  public function getPromotions() {
    return $this->promotions;
  }

  /**
   * {@inheritdoc}
   */
  public function getProject($name) {
    return $this->projects[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects($category = NULL) {
    $projects = $this->projects;
    if ($category) {
      foreach ($projects as $project_name => $project) {
        if ($project['category'] != $category) {
          unset($projects[$project_name]);
        }
      }
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings() {
    $projects = $this->projects;
    foreach ($projects as $project_name => $project) {
      if (empty($project['third_party_settings'])) {
        unset($projects[$project_name]);
      }
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $categories = [];
    $categories['config'] = [
      'title' => $this->t('Configuration management'),
    ];
    $categories['element'] = [
      'title' => $this->t('Elements'),
    ];
    $categories['integration'] = [
      'title' => $this->t('Integration'),
    ];
    $categories['mail'] = [
      'title' => $this->t('Mail'),
    ];
    $categories['migrate'] = [
      'title' => $this->t('Migrate'),
    ];
    $categories['multilingual'] = [
      'title' => $this->t('Multilingual'),
    ];
    $categories['rest'] = [
      'title' => $this->t('REST'),
    ];
    $categories['spam'] = [
      'title' => $this->t('SPAM Protection'),
    ];
    $categories['submission'] = [
      'title' => $this->t('Submissions'),
    ];
    $categories['validation'] = [
      'title' => $this->t('Validation'),
    ];
    $categories['utility'] = [
      'title' => $this->t('Utility'),
    ];
    $categories['development'] = [
      'title' => $this->t('Development'),
    ];
    return $categories;
  }

  /**
   * Initialize add-on promotions.
   *
   * @return array
   *   An associative array containing add-on promotions.
   */
  protected function initPromotions() {
    $promotions = [];

    // Lingotek.
    $img_attributes = new Attribute([
      'src' => base_path() . drupal_get_path('module', 'webform') . '/images/promotions/lingotek-logo.png',
      'alt' => $this->t('Lingotek: The Translation Network'),
      'title' => $this->t('Lingotek: The Translation Network'),
    ]);
    $promotions['promotion_lingotek'] = [];
    $promotions['promotion_lingotek']['content'] = [
      [
        '#markup' => '<img' . $img_attributes . ' />',
      ],
      [
        '#markup' => $this->t('The Lingotek-Inside Drupal Module is the only Drupal module to integrate a translation management system (TMS) directly into Drupal, thus allowing the Drupal community to use professional-grade translation technologies (e.g. machine translation, translation memory, CAT tool) without ever having to leave the comfort of the Drupal environment.'),
        '#prefix' => '<p>',
        '#suffix' => '<p>',
      ],
      [
        '#markup' => $this->t('You can help support the Webform module by signing up and trying the Lingotek-Inside Drupal Module for <strong>free</strong>. The maintainer of Webform for Drupal 8 (<a href="http://www.jrockowitz.com/">jrockowitz</a>) has become a Lingotek partner. If you become a customer of Lingotek\'s professional translation service, Jacob Rockowitz will receive a software referral fee, which will help fund his ongoing and dedicated effort to improving and making the Webform module for Drupal 8 <strong>awesome!!!</strong>'),
        '#prefix' => '<blockquote>',
        '#suffix' => '</blockquote>',
      ],
      [
        '#markup' => $this->t('It only takes 5 simple questions to indicate that the Webform module referred you to Lingotek. Then you can download and try the Lingotek module for free.'),
        '#prefix' => '<p>',
        '#suffix' => '<p>',
      ],
      ['#markup' => '<hr/>'],
      'actions' => [
        'try' => [
          '#type' => 'link',
          '#title' => $this->t('Sign up and try Lingotek'),
          '#url' => Url::fromUri('https://lingotek.com/webform'),
          '#attributes' => ['class' =>  ['button', 'button--primary']],
        ],
        'video' => [
          '#type' => 'link',
          '#title' => $this->t('Watch video'),
          '#url' => Url::fromRoute('webform.help.video', ['id' => 'promotion-lingotek']),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(1000, ['button', 'button-action', 'button-webform-play']),
        ],
      ],
    ];

    return $promotions;
  }

  /**
   * Initialize add-on projects.
   *
   * @return array
   *   An associative array containing add-on projects.
   */
  protected function initProjects() {
    $projects = [];

    // Config: Drush CMI tools.
    $projects['drush_cmi_tools'] = [
      'title' => $this->t('Drush CMI tools'),
      'description' => $this->t('Provides advanced CMI import and export functionality for CMI workflows. Drush CMI tools should be used to protect Forms from being overwritten during a configuration import.'),
      'url' => Url::fromUri('https://github.com/previousnext/drush_cmi_tools'),
      'category' => 'config',
    ];

    // Config: Configuration Ignore.
    $projects['config_ignore'] = [
      'title' => $this->t('Config Ignore'),
      'description' => $this->t('Ignore certain configuration during import'),
      'url' => Url::fromUri('https://www.drupal.org/project/config_ignore'),
      'category' => 'config',
    ];

    // Config: Configuration Split.
    $projects['config_split'] = [
      'title' => $this->t('Configuration Split'),
      'description' => $this->t('Provides configuration filter for importing and exporting split config.'),
      'url' => Url::fromUri('https://www.drupal.org/project/config_split'),
      'category' => 'config',
    ];

    // Element: Webform Crosspage Conditions.
    $projects['webform_crosspage_conditions'] = [
      'title' => $this->t('Webform Crosspage Conditions'),
      'description' => $this->t('Provides the handler that evaluates the field state conditions with the fields from other than current page'),
      'url' => Url::fromUri('https://github.com/artemvd/webform_crosspage_conditions'),
      'category' => 'element',
    ];

    // Element: Webform Layout Container.
    $projects['webform_layout_container'] = [
      'title' => $this->t('Webform Layout Container'),
      'description' => $this->t("Provides a layout container element to add to a webform, which uses old fashion floats to support legacy browsers that don't support CSS Flexbox (IE9 and IE10)."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_layout_container'),
      'category' => 'element',
    ];

    // Element: Webform Node Element.
    $projects['webform_node_element'] = [
      'title' => $this->t('Webform Node Element'),
      'description' => $this->t("Provides a 'Node' element to display node content as an element on a webform. Can be modified dynamically using an event handler."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_node_element'),
      'category' => 'element',
    ];

    // Element: Webform Score.
    $projects['webform_score'] = [
      'title' => $this->t('Webform Score'),
      'description' => $this->t('Lets you score an individual user\'s answers, then store and display the scores.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_score'),
      'category' => 'element',
    ];

    // Element: Webform Crafty Clicks.
    $projects['webform_craftyclicks'] = [
      'title' => $this->t('Webform Crafty Clicks'),
      'description' => $this->t('Adds Crafty Clicks UK postcode lookup to the Webform Address composite element.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_craftyclicks'),
      'category' => 'element',
    ];

    // Integration: Webform iContact.
    $projects['webform_icontact'] = [
      'title' => $this->t('Webform iContact'),
      'description' => $this->t('Send Webform submissions to iContact list.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/ibakayoko/2853326'),
      'category' => 'integration',
    ];

    // Integrations: Webform MailChimp.
    $projects['webform_mailchimp'] = [
      'title' => $this->t('Webform MailChimp'),
      'description' => $this->t('Posts form submissions to MailChimp list.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mailchimp'),
      'category' => 'integration',
    ];

    // Integrations: Webform Product
    $projects['webform_product'] = [
      'title' => $this->t('Webform Product'),
      'description' => $this->t('Links commerce products to webform elements.'),
      'url' => Url::fromUri('https://github.com/chx/webform_product'),
      'category' => 'integration',
    ];

    // Integrations: Webform Slack integration.
    $projects['webform_slack'] = [
      'title' => $this->t('Webform Slack'),
      'description' => $this->t('Provides a Webform handler for posting a message to a slack channel when a submission is saved.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/smaz/2833275'),
      'category' => 'integration',
    ];

    // Integrations: Webform Stripe integration.
    $projects['stripe_webform'] = [
      'title' => $this->t('Webform Stripe'),
      'description' => $this->t('Provides a stripe webform element and default handlers'),
      'url' => Url::fromUri('https://www.drupal.org/project/stripe_webform'),
      'category' => 'integration',
    ];

    // Integrations: OpenInbound for Drupal.
    $projects['openinbound'] = [
      'title' => $this->t('OpenInbound for Drupal'),
      'description' => $this->t('OpenInbound tracks contacts and their interactions on websites.'),
      'url' => Url::fromUri('https://www.drupal.org/project/openinbound'),
      'category' => 'integration',
    ];

    // Integrations: Salesforce Web-to-Lead Webform Data Integration.
    $projects['sfweb2lead_webform'] = [
      'title' => $this->t('Salesforce Web-to-Lead Webform Data Integration'),
      'description' => $this->t('Integrates Salesforce Web-to-Lead Form feature with various webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sfweb2lead_webform'),
      'category' => 'integration',
    ];


    // Mail: Mail System.
    $projects['mailsystem'] = [
      'title' => $this->t('Mail System'),
      'description' => $this->t('Provides a user interface for per-module and site-wide mail system selection.'),
      'url' => Url::fromUri('https://www.drupal.org/project/mailsystem'),
      'category' => 'mail',
    ];

    // Mail: SMTP Authentication Support.
    $projects['smtp'] = [
      'title' => $this->t('SMTP Authentication Support'),
      'description' => $this->t('Allows for site emails to be sent through an SMTP server of your choice.'),
      'url' => Url::fromUri('https://www.drupal.org/project/smtp'),
      'category' => 'mail',
    ];

    // Multilingual: Lingotek Translation.
    $projects['lingotek'] = [
      'title' => $this->t('Lingotek Translation.'),
      'description' => $this->t('Translates content, configuration, and interface using the Lingotek Translation Management System.'),
      'url' => Url::fromUri('https://www.drupal.org/project/lingotek'),
      'category' => 'multilingual',
    ];

    // Migrate: Webform Migrate.
    $projects['webform_migrate'] = [
      'title' => $this->t('Webform Migrate'),
      'description' => $this->t('Provides migration routines from d6, d7 webform to d8 webform.'),
      'url' => Url::fromUri('https://github.com/heshanlk/webform_migrate'),
      'category' => 'migrate',
    ];

    // Migrate: YAML Form Migrate.
    $projects['yamlform_migrate'] = [
      'title' => $this->t('YAML Form Migrate'),
      'description' => $this->t('Provides migration routines from Drupal 6 YAML Form module to Drupal 8 YAML Form module.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/dippers/2819169'),
      'category' => 'migrate',
    ];

    // Spam: Antibot.
    $projects['antibot'] = [
      'title' => $this->t('Antibot'),
      'description' => $this->t('Prevent forms from being submitted without JavaScript enabled.'),
      'url' => Url::fromUri('https://www.drupal.org/project/antibot'),
      'category' => 'spam',
      'third_party_settings' => TRUE,
    ];

    // Spam: CAPTCHA.
    $projects['captcha'] = [
      'title' => $this->t('CAPTCHA'),
      'description' => $this->t('Provides CAPTCHA for adding challenges to arbitrary forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/captcha'),
      'category' => 'spam',
    ];

    // Spam: Honeypot.
    $projects['honeypot'] = [
      'title' => $this->t('Honeypot'),
      'description' => $this->t('Mitigates spam form submissions using the honeypot method.'),
      'url' => Url::fromUri('https://www.drupal.org/project/honeypot'),
      'category' => 'spam',
      'third_party_settings' => TRUE,
    ];

    // Submissions: Webform Views Integration.
    $projects['webform_views'] = [
      'title' => $this->t('Webform Views'),
      'description' => $this->t('Integrates Webform 8.x-5.x and Views modules.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_views'),
      'category' => 'submission',
    ];

    // Submissions: Webform Queue.
    $projects['webform_queue'] = [
      'title' => $this->t('Webform Queue'),
      'description' => $this->t('Posts form submissions into a Drupal queue.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_queue'),
      'category' => 'submission',
    ];

    // REST: Webform REST.
    $projects['webform_rest'] = [
      'title' => $this->t('Webform REST'),
      'description' => $this->t('Retrieve and submit webforms via REST.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_rest'),
      'category' => 'rest',
    ];

    // Utility: Webform Encrypt.
    $projects['wf_encrypt'] = [
      'title' => $this->t('Webform Encrypt'),
      'description' => $this->t('Provides encryption for webform elements.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_encrypt'),
      'category' => 'utility',
    ];

    // Utility: IMCE.
    $projects['imce'] = [
      'title' => $this->t('IMCE'),
      'description' => $this->t('IMCE is an image/file uploader and browser that supports personal directories and quota.'),
      'url' => Url::fromUri('https://www.drupal.org/project/imce'),
      'recommended' => TRUE,
      'category' => 'utility',
    ];

    // Utility: Token.
    $projects['token'] = [
      'title' => $this->t('Token'),
      'description' => $this->t('Provides a user interface for the Token API and some missing core tokens.'),
      'url' => Url::fromUri('https://www.drupal.org/project/token'),
      'recommended' => TRUE,
      'category' => 'utility',
    ];


    // Validation: Clientside Validation.
    $projects['clientside_validation'] = [
      'title' => $this->t('Clientside Validation'),
      'description' => $this->t('Adds clientside validation to forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/clientside_validation'),
      'category' => 'validation',
    ];

    // Validation: Validators.
    $projects['validators'] = [
      'title' => $this->t('Validators'),
      'description' => $this->t('Provides Symfony (form) Validators for Drupal 8.'),
      'url' => Url::fromUri('https://www.drupal.org/project/validators'),
      'category' => 'validation',
    ];

    // Devel: Maillog / Mail Developer
    $projects['maillog'] = [
      'title' => $this->t('Maillog / Mail Developer'),
      'description' => $this->t('Utility to log all Mails for debugging purposes. It is possible to suppress mail delivery for e.g. dev or staging systems.'),
      'url' => Url::fromUri('https://www.drupal.org/project/maillog'),
      'category' => 'development',
    ];

    return $projects;
  }

}

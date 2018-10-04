<?php

namespace Drupal\social_feed_fetcher\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with examples on how to use cron.
 */
class SocialPostSettingsForm extends ConfigFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;


  /**
   * Request time value.
   *
   * @var int
   */
  private $requestTime;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, CronInterface $cron, StateInterface $state) {
    parent::__construct($config_factory);
    $this->currentUser = $current_user;
    $this->cron = $cron;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('cron'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_feed_fetcher';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_feed_fetcher.settings');
    $this->requestTime = \Drupal::time()->getRequestTime();

    $next_execution = $this->state->get('social_feed_fetcher.next_execution');
    $next_execution = !empty($next_execution) ? $next_execution : $this->requestTime;

    $args = [
      '%time' => date_iso8601($this->state->get('social_feed_fetcher.next_execution')),
      '%seconds' => $next_execution - $this->requestTime,
    ];
    $form['status']['last'] = [
      '#type' => 'item',
      '#markup' => $this->t('The Social Feed Fetcher will next execute the first time the cron runs after %time (%seconds seconds from now)', $args),
    ];

    $form['facebook'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook settings'),
      '#open' => TRUE,
    ];

    $form['facebook']['facebook_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('facebook_enabled'),
    ];

    $form['facebook']['fb_page_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Page Name'),
      '#default_value' => $config->get('fb_page_name'),
      '#description' => $this->t('eg. If your Facebook page URL is this http://www.facebook.com/YOUR_PAGE_NAME, <br />then you just need to add this YOUR_PAGE_NAME above.'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => $config->get('facebook_enabled') ? TRUE : FALSE,
      '#states'        => [
        'visible'  => [
          ':input[name="facebook_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];

    $form['facebook']['fb_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook App ID'),
      '#default_value' => $config->get('fb_app_id'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => $config->get('facebook_enabled') ? TRUE : FALSE,
      '#states'        => [
        'visible'  => [
          ':input[name="facebook_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];

    $form['facebook']['fb_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Secret Key'),
      '#default_value' => $config->get('fb_secret_key'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => $config->get('facebook_enabled') ? TRUE : FALSE,
      '#states'        => [
        'visible'  => [
          ':input[name="facebook_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];

    $form['facebook']['fb_no_feeds'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of Feeds'),
      '#default_value' => $config->get('fb_no_feeds'),
      '#size' => 60,
      '#maxlength' => 60,
      '#max' => 30,
      '#min' => 1,
      '#states'        => [
        'visible'  => [
          ':input[name="facebook_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];


    $form['twitter'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter settings'),
      '#open' => TRUE,
    ];

    $form['twitter']['twitter_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('twitter_enabled'),
    ];

    $form['twitter']['tw_consumer_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Twitter Consumer Key'),
      '#default_value' => $config->get('tw_consumer_key'),
      '#size'          => 60,
      '#maxlength'     => 100,
      '#required' => $config->get('twitter_enabled') ? TRUE : FALSE,
      '#states'        => [
        'visible'  => [
          ':input[name="twitter_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];
    $form['twitter']['tw_consumer_secret'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Twitter Consumer Secret'),
      '#default_value' => $config->get('tw_consumer_secret'),
      '#size'          => 60,
      '#maxlength'     => 100,
      '#required' => $config->get('twitter_enabled') ? TRUE : FALSE,
      '#states'        => [
        'visible'  => [
          ':input[name="twitter_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];
    $form['twitter']['tw_access_token'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Twitter Access Token'),
      '#default_value' => $config->get('tw_access_token'),
      '#size'          => 60,
      '#maxlength'     => 100,
      '#required' => $config->get('twitter_enabled') ? TRUE : FALSE,
      '#states'        => [
        'visible'  => [
          ':input[name="twitter_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];
    $form['twitter']['tw_access_token_secret'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Twitter Access Token Secret'),
      '#default_value' => $config->get('tw_access_token_secret'),
      '#size'          => 60,
      '#maxlength'     => 100,
      '#required' => $config->get('twitter_enabled') ? TRUE : FALSE,
      '#states'        => [
        'visible'  => [
          ':input[name="twitter_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];
    $form['twitter']['tw_count'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Tweets Count'),
      '#default_value' => $config->get('tw_count'),
      '#size'          => 60,
      '#maxlength'     => 100,
      '#min'           => 1,
      '#max' => 30,
      '#states'        => [
        'visible'  => [
          ':input[name="twitter_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];

    $form['instagram'] = [
      '#type' => 'details',
      '#title' => $this->t('Instagram settings'),
      '#open' => TRUE,
    ];

    $form['instagram']['instagram_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('instagram_enabled'),
    ];
    $form['instagram']['header']['#markup'] = $this->t('To get Client ID you need to manage clients from your instagram account detailed information <a href="@admin" target="@blank">here</a>.', [
      '@admin' => Url::fromRoute('help.page',
        ['name' => 'socialfeed'])->toString(),
      '@blank' => '_blank',
    ]);
    $form['instagram']['in_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('Client ID from Instagram account'),
      '#default_value' => $config->get('in_client_id'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => $config->get('instagram_enabled') ? TRUE : FALSE,
      '#states'        => [
        'visible'  => [
          ':input[name="instagram_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];
    $form['instagram']['in_redirect_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URI'),
      '#description' => $this->t('Redirect URI from Instagram account'),
      '#default_value' => $config->get('in_redirect_uri'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => $config->get('instagram_enabled') ? TRUE : FALSE,
      '#states'        => [
        'visible'  => [
          ':input[name="instagram_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];
    $form['instagram']['in_auth_link'] = [
      '#type' => 'item',
      '#title' => $this->t('Generate Instagram Access Token'),
      '#description' => $this->t('Access this URL in your browser https://instagram.com/oauth/authorize/?client_id=&lt;your_client_id&gt;&redirect_uri=&lt;your_redirect_uri&gt;&response_type=token, you will get the access token.'),
      '#default_value' => $config->get('in.auth_link'),
      '#markup' => $this->t('Check <a href="@this" target="_blank">this</a> article.', [
        '@this' => Url::fromUri('http://jelled.com/instagram/access-token')
          ->toString(),
      ]),
      '#states'        => [
        'visible'  => [
          ':input[name="instagram_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];
    $form['instagram']['in_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $config->get('in_access_token'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => $config->get('instagram_enabled') ? TRUE : FALSE,
      '#states'        => [
        'visible'  => [
          ':input[name="instagram_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];
    $form['instagram']['in_picture_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Picture Count'),
      '#default_value' => $config->get('in_picture_count'),
      '#size' => 60,
      '#maxlength' => 100,
      '#min' => 1,
      '#max' => 30,
      '#states'        => [
        'visible'  => [
          ':input[name="instagram_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];
    if ($config->get('in_access_token')) {
      $form['instagram']['feed'] = [
        '#type' => 'item',
        '#title' => $this->t('Feed URL'),
        '#markup' => $this->t('https://api.instagram.com/v1/users/self/feed?access_token=@access_token&count=@picture_count',
          [
            '@access_token' => $config->get('in_access_token'),
            '@picture_count' => $config->get('in_picture_count'),
          ]
        ),
      ];
    }
    $form['instagram']['in_picture_resolution'] = [
      '#type' => 'select',
      '#title' => $this->t('Picture Resolution'),
      '#default_value' => $config->get('in_picture_resolution'),
      '#options' => [
        'thumbnail' => $this->t('Thumbnail'),
        'low_resolution' => $this->t('Low Resolution'),
        'standard_resolution' => $this->t('Standard Resolution'),
      ],
      '#states'        => [
        'visible'  => [
          ':input[name="instagram_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];
    $form['instagram']['in_post_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show post URL'),
      '#default_value' => $config->get('in_post_link'),
      '#states'        => [
        'visible'  => [
          ':input[name="instagram_enabled"]' => ['checked' => TRUE],
        ]
      ]
    ];

    if ($this->currentUser->hasPermission('administer site configuration')) {
      $form['cron_run'] = [
        '#type' => 'details',
        '#title' => $this->t('Run cron manually'),
        '#open' => TRUE,
      ];
      $form['cron_run']['cron_trigger']['actions'] = ['#type' => 'actions'];
      $form['cron_run']['cron_trigger']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Run cron now'),
        '#submit' => [[$this, 'cronRun']],
      ];
    }

    $form['configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('Schedule Cron'),
      '#open' => TRUE,
    ];
    $form['configuration']['social_feed_fetcher_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Cron interval'),
      '#description' => $this->t('Time after which cron will respond to a processing request.'),
      '#default_value' => $config->get('cron.interval'),
      '#options' => [
        60 => $this->t('1 minute'),
        300 => $this->t('5 minutes'),
        600 => $this->t('10 minutes'),
        900 => $this->t('15 minutes'),
        1800 => $this->t('30 minutes'),
        3600 => $this->t('1 hour'),
        21600 => $this->t('6 hours'),
        86400 => $this->t('1 day'),
      ],
    ];

    $allowed_formats = filter_formats();
    foreach (filter_formats() as $format_name => $format) {
      $allowed_formats[$format_name] = $format->label();
    }

    $form['formats'] = [
      '#type' => 'details',
      '#title' => $this->t('Post Format'),
      '#open' => TRUE,
    ];

    $form['formats']['formats_post_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Post format'),
      '#default_value' => $config->get('formats.post_format'),
      '#options' => $allowed_formats,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Allow user to directly execute cron, optionally forcing it.
   */
  public function cronRun(array &$form, FormStateInterface &$form_state) {
    // Use a state variable to signal that cron was run manually from this form.
    $this->state->set('social_feed_fetcher.next_execution', 0);
    $this->state->set('social_feed_fetcher_show_status_message', TRUE);
    if ($this->cron->run()) {
      drupal_set_message($this->t('Cron ran successfully.'));
    }
    else {
      drupal_set_message($this->t('Cron run failed.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config =  $this->config('social_feed_fetcher.settings');
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_feed_fetcher.settings'];
  }

}

<?php

namespace Drupal\flood_unblock\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\flood_unblock\FloodUnblockManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;

/**
 * Admin form of Flood unblock.
 */
class FloodUnblockAdminForm extends FormBase {

  /**
   * @var \Drupal\flood_unblock\FloodUnblockManager
   */
  protected $floodUnblockManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(FloodUnblockManager $floodUnblockManager, ModuleHandlerInterface $moduleHandler) {

    $this->floodUnblockManager = $floodUnblockManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood_unblock.flood_unblock_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flood_unblock_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get ip entries from flood table.
    $flood_ip_entries = $this->floodUnblockManager->get_blocked_ip_entries();
    // Get user entries from flood table.
    $flood_user_entries = $this->floodUnblockManager->get_blocked_user_entries();
    $entries = $flood_ip_entries + $flood_user_entries;

    // Get config setting for flood unblock.
    $config = $this->config('flood_unblock.settings');
    $ip_limit = $config->get('flood_unblock.user.failed_login_ip_limit');
    $user_limit = $config->get('flood_unblock.user.failed_login_user_limit');

    $blocks = array();
    foreach ($entries as $identifier => $entry) {
      $blocks[$identifier] = array(
        'identifier' => $identifier,
        'type'       => $entry['type'],
        'count'      => $entry['count'],
      );
      if ($entry['type'] == 'ip') {
        $blocks[$identifier]['ip'] = $entry['ip'] . $entry['location'];
        $blocks[$identifier]['uid'] = '';
        $blocks[$identifier]['blocked'] = $entry['count'] >= $ip_limit ? "Yes" : "";
      }
      if ($entry['type'] == 'user') {
        $blocks[$identifier]['ip'] = $entry['ip'] . $entry['location'];
        $blocks[$identifier]['uid'] = $entry['username'];
        $blocks[$identifier]['blocked'] = $entry['count'] >= $user_limit ? "Yes" : "";
      }
    }

    $header = array(
      'blocked' => $this->t('Blocked'),
      'type'    => $this->t('Type of block'),
      'count'   => $this->t('Count'),
      'uid'     => $this->t('Account name'),
      'ip'      => $this->t('IP Address'),
    );

    $options = array();
    foreach ($blocks as $block) {
      $options[$block['identifier']] = array(
        'blocked' => $block['blocked'],
        'type'    => $block['type'],
        'count'   => $block['count'],
        'uid'     => $block['uid'],
        'ip'      => $block['ip'],
      );
    }

    $prefix = 'Drupal has two types of blocks available:<br />' .
      '<ul><li>One where the incorrect password of an existing user account is being used. The user account being used and the IP address is logged.' .
      '<li>One where an incorrect user name is being used. The IP address is logged.</ul>';

    $prefix .= '<br />';

    if (!function_exists('smart_ip_get_location')) {
      $prefix .= 'If the ' . \Drupal::l('Smart IP', Url::fromUri('http://drupal.org/project/smart_ip')) . ' module is loaded, the physical location of the IP address will be shown.<br />';
      $prefix .= '<br />';
    }

    $form['table'] = array(
      '#type'    => 'tableselect',
      '#header'  => $header,
      '#options' => $options,
      '#empty'   => $this->t('There are no failed logins at this time.'),
      '#prefix'  => $this->t($prefix),
    );

    $form['submit'] = array(
      '#type'  => 'submit',
      '#value' => $this->t('Clear flood'),
    );

    if (count($entries) == 0) {
      $form['submit']['#disabled'] = TRUE;
    }

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entries = $form_state->getValue('table');
    $selected_entries = array_filter($entries, function($selected) {
      return $selected !== 0;
    });
    if (empty($selected_entries)) {
      $form_state->setErrorByName('table', $this->t('Please make a selection.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('table') as $value) {
      if ($value !== 0) {
        $type = $form['table']['#options'][$value]['type'];
        switch ($type) {
          case 'ip':
            $type = 'user.failed_login_ip';
            break;

          case 'user':
            $type = 'user.failed_login_user';
            break;

        }

        $identifier = $value;
        $this->floodUnblockManager->flood_unblock_clear_event($type, $identifier);

      }
    }
  }
}

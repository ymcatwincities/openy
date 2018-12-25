<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\Entity\MemberChance;
use Drupal\ymca_retention\InstantWin;
use Drupal\ymca_retention\RegularUpdater;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form to fix issue with member emails.
 */
class FixMembersForm extends FormBase {

  /**
   * Instant win service.
   *
   * @var \Drupal\ymca_retention\InstantWin
   */
  protected $instantWin;

  /**
   * FixMembersForm constructor.
   *
   * @param \Drupal\ymca_retention\InstantWin $instant_win
   *   Instant win service.
   */
  public function __construct(InstantWin $instant_win) {
    $this->instantWin = $instant_win;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('ymca_retention.instant_win'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_fix_members';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['statistics'] = [
      '#type' => 'details',
      '#title' => $this->t('Statistics'),
      '#open' => TRUE,
    ];
    $query = \Drupal::entityQuery('ymca_retention_member')
      ->exists('personify_email')
      ->notExists('mail');
    $members_count = $query->execute();
    $form['statistics']['members'] = [
      '#markup' => $this->t('<p>@count members without email but with Personify email</p>', [
        '@count' => empty($members_count) ? 0 : count($members_count),
      ]),
    ];
    $query = \Drupal::entityQuery('ymca_retention_member')
      ->notExists('personify_email')
      ->notExists('mail');
    $members_count = $query->execute();
    $form['statistics']['members_no_email'] = [
      '#markup' => $this->t('<p>@count members without both emails</p>', [
        '@count' => empty($members_count) ? 0 : count($members_count),
      ]),
    ];

    $query = \Drupal::entityQuery('ymca_retention_member_chance')
      ->condition('winner', 1)
      ->notExists('order_id');
    $result = $query->execute();
    $chances = MemberChance::loadMultiple($result);
    $form['statistics']['prizes'] = [
      '#markup' => $this->t('<p>@count not sent prizes</p>', [
        '@count' => empty($chances) ? 0 : count($chances),
      ]),
    ];

    $form['demo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('DISABLE ONLY TO SEND PRIZES ON PRODUCTION'),
      '#description' => $this->t('If execute with enabled checkbox then will be generated fake order id, to verify that everything is ok.'),
      '#default_value' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Set personify email to members'),
      '#button_type' => 'primary',
    );
    $form['actions']['send_prizes'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Send prizes to winners'),
      '#button_type' => 'secondary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    switch ($button['#button_type']) {
      case 'primary':
        $query = \Drupal::entityQuery('ymca_retention_member')
          ->exists('personify_email')
          ->notExists('mail');
        $result = $query->execute();
        $members = Member::loadMultiple($result);
        /* @var Member $member */
        foreach ($members as $member) {
          $email = ymca_retention_clean_personify_email($member->getPersonifyEmail());
          $member->setEmail($email);
          $member->setPersonifyEmail($email);
          $member->save();
        }
        drupal_set_message($this->t('@count members has been processed', [
          '@count' => count($members),
        ]));
        break;

      case 'secondary':
        $query = \Drupal::entityQuery('ymca_retention_member_chance')
          ->condition('winner', 1)
          ->notExists('order_id');
        $result = $query->execute();
        $chances = MemberChance::loadMultiple($result);

        $count = 0;
        /* @var \Drupal\ymca_retention\Entity\MemberChance $chance */
        foreach ($chances as $chance) {
          /* @var Member $member */
          $member = Member::load($chance->get('member')->target_id);
          $email = $member->getEmail();
          if (empty($email)) {
            continue;
          }
          if ($form_state->getValue('demo')) {
            $chance->set('order_id', '111-1111111111-11');
            $count++;
          }
          else {
            $order = $this->instantWin->generateTangoCardPrize($member, $chance->get('value')->value);
            if ($order) {
              $chance->set('order_id', $order->order_id);
              $count++;
            }
          }
          $chance->save();
        }
        drupal_set_message($this->t('@count has been sent prizes', [
          '@count' => $count,
        ]));
        break;
    }
  }

}

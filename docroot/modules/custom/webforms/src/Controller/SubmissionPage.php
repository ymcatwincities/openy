<?php

namespace Drupal\webforms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webforms\ContactForm;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements SubmissionPage.
 */
class SubmissionPage extends ControllerBase {

  /**
   * Build the page.
   */
  public function buildPage($contact_form) {
    /* @var ContactForm $contact_form */
    $contact_form = $this->entityTypeManager()
      ->getStorage('contact_form')
      ->load($contact_form);

    if (!$contact_form->getProvideSubmissionPage()) {
      throw new NotFoundHttpException('This form doesn\'t provide submission page');
    }

    $uuid = \Drupal::request()->query->get('key');
    if (!$uuid) {
      throw new NotFoundHttpException('UUID missed');
    }

    // Load submission data.
    $submissions = $this->entityTypeManager()
      ->getStorage('contact_message')
      ->loadByProperties([
        'uuid' => $uuid,
      ]);

    // Incorrect UUID entered.
    if (!$submissions) {
      throw new NotFoundHttpException('Incorrect UUID entered');
    }

    $submission = reset($submissions);

    // Submission data available only for 1 minute.
    if (time() - $submission->created->getValue()[0]['value'] > 60) {
      throw new NotFoundHttpException();
    }

    // Retrieve submission page content.
    $submission_page_content = $contact_form->getSubmissionPageContent();
    // Replace tokens.
    $submission_page_content['value'] = \Drupal::token()->replace($submission_page_content['value'], [
      'contact_message' => $submission,
    ]);

    return [
      '#theme' => 'webform_submission_page',
      '#content' => check_markup($submission_page_content['value'], $submission_page_content['format']),
      '#title' => $this->t($contact_form->getSubmissionPageTitle()),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}

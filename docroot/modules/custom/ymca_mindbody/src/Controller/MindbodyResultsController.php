<?php

namespace Drupal\ymca_mindbody\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface;
use Drupal\ymca_mindbody\Form\MindbodyPTForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for "Mindbody results" page.
 */
class MindbodyResultsController extends ControllerBase {

  /**
   * Mindbody Proxy.
   *
   * @var MindbodyCacheProxyInterface
   */
  protected $proxy;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * MindbodyResultsController constructor.
   *
   * @param MindbodyCacheProxyInterface $cache_proxy
   *   Mindbody cache proxy.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(MindbodyCacheProxyInterface $cache_proxy, RequestStack $request_stack) {
    $this->proxy = $cache_proxy;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('mindbody_cache_proxy.client'), $container->get('request_stack'));
  }

  /**
   * Set page content.
   */
  public function content() {
    $query = $this->requestStack->getCurrentRequest()->query->all();
    $values = array(
      'location' => is_numeric($query['location']) ? $query['location'] : '',
      'program' => is_numeric($query['program']) ? $query['program'] : '',
      'session_type' => is_numeric($query['session_type']) ? $query['session_type'] : '',
      'trainer' => isset($query['trainer']) ? $query['trainer'] : '',
      'start_time' => isset($query['start_time']) ? $query['start_time'] : '',
      'end_time' => isset($query['end_time']) ? $query['end_time'] : '',
      'start_date' => isset($query['start_date']) ? $query['start_date'] : '',
      'end_date' => isset($query['end_date']) ? $query['end_date'] : '',
      'bookable_item_id' => isset($query['bookable_item_id']) ? $query['bookable_item_id'] : '',
    );

    $form = new MindbodyPTForm($this->proxy);
    $search_results = $form->getSearchResults($values);

    return [
      '#markup' => render($search_results),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Set Title.
   */
  public function setTitle() {
    return $this->t('Personal Training Schedules');
  }

  /**
   * Minbody PT book callback.
   */
  public function book() {
    $response = new AjaxResponse();

    $query = $this->requestStack->getCurrentRequest()->query->all();
    if (!MindbodyPTForm::validateToken($query)) {
      return $this->invalidTokenResponse();
    }

    $output[] = $this->t('Token is valid.');
    if ($personify_authenticated = \Drupal::request()->cookies->has('Drupal_visitor_personify_authorized')) {
      // Book item if user is authenticated in Personify.
      if ($this->bookItem($query)) {
        // Successfully booked.
        $output[] = $this->t('Successfully booked.');
      }
      else {
        // Booking failed.
        $output[] = $this->t('The booking process failed.');
      }
    }
    else {
      // Redirect to Personify login if user isn't authenticated there.
      return $this->redirectToPersonifyLogin();
    }

    $output[] = print_r($query, TRUE);

    $content = '<div class="popup-content">' . implode('<br>', $output) . '</div>';
    $options = array(
      'dialogClass' => 'popup-dialog-class',
      'width' => '300',
      'height' => '300',
    );
    $title = $this->t('Booking');
    $response->addCommand(new OpenModalDialogCommand($title, $content, $options));

    return $response;
  }

  /**
   * Custom response callback.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response object.
   */
  private function invalidTokenResponse() {
    $query = $this->requestStack->getCurrentRequest()->query->all();

    $output = [];
    $output[] = $this->t('Token is invalid.');
    $output[] = $this->t('Not booked.');
    $output[] = $this->t('Refresh the page.');
    $output[] = print_r($query, TRUE);

    $response = new AjaxResponse();
    $content = '<div class="token-invalid-popup-content">' . implode('<br>', $output) . '</div>';
    $options = array(
      'dialogClass' => 'popup-dialog-class-error',
      'width' => '300',
      'height' => '300',
    );
    $title = $this->t('Error');
    $response->addCommand(new OpenModalDialogCommand($title, $content, $options));

    return $response;
  }

  /**
   * Books Mindbody item.
   *
   * @param array $data
   *   Array of required item parameters.
   *
   * @return bool
   *   The state of booking.
   */
  private function bookItem(array $data) {
    // TODO: implement method.
    return mt_rand(0, 100) > 50;
  }

  /**
   * Return redirect AJAX response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AJAX response object, that redirects to Personify login.
   */
  private function redirectToPersonifyLogin() {
    $query = $this->requestStack->getCurrentRequest()->query->all();
    $args = MindbodyPTForm::getTokenArgs();
    foreach (array_keys($query) as $key) {
      if (!in_array($key, $args)) {
        unset($query[$key]);
      }
    }
    // Build return url.
    $destination = Url::fromRoute('ymca_mindbody.pt.results', [], [
      'query' => $query,
    ]);
    // Build Personify login url.
    $redirect_url = Url::fromRoute('ymca_personify.personify_login', [], [
      'query' => [
        'dest' => $destination->toString(),
      ],
    ]);

    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($redirect_url->toString()));

    return $response;
  }

}

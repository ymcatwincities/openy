<?php

namespace Drupal\activenet_registration\Plugin\Field\FieldFormatter;
     
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\activenet\ActivenetClientFactory;
use Drupal\core\Config\ConfigFactory;
use Drupal\Core\Datetime\DrupalDateTime;
     
/**
 * Plugin implementation of the 'activenet_registration' formatter.
 *
 * @FieldFormatter(
 *   id = "activenet_registration_formatter",
 *   label = @Translation("ActiveNet Registration"),
 *   field_types = {
 *     "activenet_registration",
 *   }
 * )
 */

class ActiveRegisterFormatter extends FormatterBase {

  private $base_uri; 

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    
    // Create a ActivenetClient from the ActivenetClientFactory.
    $config = \Drupal::configFactory();
    $clientFactory = new ActivenetClientFactory($config);
    $client = $clientFactory->get();
    
    $elements = [];
    
    $this->base_uri = \Drupal::config('activenet_registration.settings')->get('base_uri');

    foreach ($items as $delta => $item) {
      foreach($item as $param => $value) {
        if(@$value->getValue()) {
          switch($param) {
            case 'site':
              $args['site_ids'] = $value->getValue();
              break;
            case 'program_type':
              if($item->activity_flex == 'flex_reg') $args['program_type_id'] = $value->getValue();
              break;
            case 'activity_type':
              if($item->activity_flex == 'activity') $args['activity_type_id'] = $value->getValue();
              break;
            case 'category':
              $args['category_id'] = $value->getValue();
              break;
            case 'other_category':
              $args['other_category_id'] = $value->getValue();
              break;
            case 'gender':
              if($value->getValue() < 12) $args['gender'] = $value->getValue();
              break;
            case 'activity_name':
              ($item->activity_flex == 'activity' ? $args['activity_name'] = $value->getValue() : $args['program_name'] = $value->getValue());
              break;
          }
        }
      }

      switch($item->activity_flex) {
        case 'activity':
          $args['activity_status_id'] = 0;
          $args['first_date_range_from'] = date('Y-m-d', time() - (7 * 24 * 60 * 60));
          $activites = $client->getActivities($args);
          $daysOfTheWeek ='/Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday/';

          foreach($activites as $key => $activity){
            $start_date = new DrupalDateTime($activity->default_beginning_date);
            $end_date = new DrupalDateTime($activity->default_ending_date);
            preg_match_all($daysOfTheWeek, $activity->default_pattern_dates, $day_matches);
            preg_match('/\d+:\d+ [APM]+/', $activity->default_pattern_dates, $start_time_match);
            preg_match('/\d+h\d+m|\d+h|\d+m/', $activity->default_pattern_dates, $duration_match);
            if(preg_match('/\d+h\d+m/', $duration_match[0])) {
              $duration = preg_replace("/(\d+)h(\d+)m/", "+$1 hours $2 minutes", $duration_match[0]);               
            }
            elseif(preg_match('/\d+h/', $duration_match[0])) {
              $duration = preg_replace("/(\d+)h/", "+$1 hours", $duration_match[0]); 
            }
            else {
              $duration = preg_replace("/(\d+)m/", "+$1 minutes", $duration_match[0]); 
            }
            $end_time = new DrupalDateTime($start_time_match[0]);
            $end_time->modify($duration);
            $date_pattern = $start_time_match[0] . ' - ' . $end_time->format('g:i A') . ', ';
            foreach($day_matches[0] as $key => $day) {
              if($key != 0 ) $date_pattern .= ', ';
              $date_pattern .= $day;
            }
            $element = [
              '#theme' => 'activenet_activity_registration',
              '#name' => $activity->activity_name,
              '#registration_url' => $this->base_uri . 'Activity_Search/' . $activity->activity_id,
              '#gender' => $activity->gender,
              '#site_name' =>  $activity->site_name,
              '#date_start' => $start_date->format('M j'),
              '#date_end' => $end_date->format('M j'),
              '#date_pattern' => $date_pattern,
              '#cache' => [
                'tags' => ['site:' . $activity->site_name, 'activity_id:' . $activity->activity_id],
                'keys' => ['activenet_registration', $activity->activity_id],
                'max-age' => 86400,
                'context' => 'route.name',
              ],
            ];

            $elements[] = $element;
          }
          break;

        case 'flex':
          $args['program_status_id'] = 0;
          try{
            $programs = $client->getFlexRegPrograms($args);
            foreach($programs as $key => $program){
              $element = [
                '#theme' => 'activenet_flex_registration',
                '#name' => $program->program_name,
                '#registration_url' => $this->base_uri . 'ActiveNet_Home?FileName=onlineDCProgramDetail.sdi&dcprogram_id=' . $program->program_id,
                '#gender' => ['#markup' => '<div class="gender">' . $program->gender . '</div>'],
                '#site_name' =>  $program->site_name,
                '#cache' => [
                  'tags' => ['site:' . $program->site_name, 'program_id:' . $program->program_id],
                  'keys' => ['activenet_registration', $program->program_id],
                  'max-age' => 86400,
                  'context' => 'route.name',
                ],
              ];
              $elements[] = $element;
            }
          }
          catch(\Exception $e) {
            
          }
          break;
      }

    }

    return $elements;
  }
}
<?php

namespace Drupal\ymca_personify_upgrade;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Defines a Personify Upgrade service.
 */
class PersonifyUpgrade {

  /**
   * The base path.
   */
  const BASE_PATH  = 'https://www.ymcamn.org//';

  /**
   * The logger factory.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The config factory.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * Creates a new PersonifyUpgrade.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, ConfigFactory $config_factory) {
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * Run Upgrade.
   */
  public function run() {
    $csvFile = file(drupal_get_path('module', 'ymca_personify_upgrade') . '/source/url_translation.csv');
    // Prepare links data from source csv file.
    $data = $this->prepareLinksArray($csvFile);
    // Make links replacement in entities without save.
    $entities = $this->replaceLinks($data);
    // Setup batch process to save entities.
    $operations = [];
    $entities = array_chunk($entities, 10, true);
    foreach ($entities as $row) {
      $operations[] = [
        '\Drupal\ymca_personify_upgrade\PersonifyUpgrade::saveEntities',
        [$row]
      ];
    }
    $batch = array(
      'title' => t('Updating Links...'),
      'operations' => $operations,
      'finished' => '\Drupal\ymca_personify_upgrade\PersonifyUpgrade::finishedCallback',
    );
    batch_set($batch);
  }

  /**
   * Save Entities.
   */
  public static function saveEntities($entities, &$context){
    $message = 'Updating Links...';
    $results = 1;
    foreach ($entities as $entity) {
      $results++;
      $entity->save();
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  /**
   * Helper function to replace links by mapping.
   */
  protected function replaceLinks($data) {
    $data_content = $entities_to_save = [];
    $count = 0;
    // Load content.
    foreach ($data['paths'] as $type => $ids) {
      $data_content[$type] = \Drupal::service('entity.manager')->getStorage($type)->loadMultiple($ids);
    }
    foreach ($data_content as $type => $entities) {
      foreach ($entities as $entity) {
        $config = $this->configFactory->get('ymca_personify_upgrade.settings')->get('tables_whitelist');
        $fields_data = array_shift($config);
        foreach ($fields_data as $field_name => $field_data) {
          $field_name = str_replace($type . '__', '', $field_name);
          //$abu = '<article class="content_wrapper panel-group" id="component_153748"> <article class="panel panel-default content-expander" id="component_153745"> <div class="panel-heading"> <div class="panel-title"> <h2 id="component_146018"><a class="accordion-toggle collapsed" data-parent=".panel-group" data-toggle="collapse" href="#collapse153745">Register for Classes and Programs</a></h2> </div> </div> <div class="panel-collapse collapse" id="collapse153745"> <div class="panel-body"> <article class="richtext" id="component_146026"> <h3>Search by:</h3> <ul> <li><a href="https://ygtcprod2.personifycloud.com/personifyebusiness/Default.aspx?TabId=177&amp;catcode=group_exercise">Trainer-Led Group Classes</a></li> <li><a href="https://ygtcprod2.personifycloud.com/personifyebusiness/Default.aspx?TabId=177&amp;catcode=arc_cert" title="Certifications and Lifeguard Training">Certifications and Lifeguard Training</a></li> <li><a href="/child_care__preschool/" title="Child Care">Child Care</a></li> <li><a href="https://ygtcprod2.personifycloud.com/personifyebusiness/Default.aspx?TabId=177&amp;catcode=swim_aquatics" title="Find Swim Lessons &amp; Aquatics Classes">Swim Lessons and Aquatics</a></li> <li><a href="https://ygtcprod2.personifycloud.com/personifyebusiness/Default.aspx?TabId=177&amp;pc=DC&amp;catcode=DAY_CAMP&amp;autosearch=Y" title="Day Camps">Day Camps</a></li> <li><a href="https://ygtcprod2.personifycloud.com/personifyebusiness/Default.aspx?TabId=177&amp;catcode=family" title="Family Programs">Family Programs</a></li> <li><a href="https://ygtcprod2.personifycloud.com/personifyebusiness/Default.aspx?TabId=177&amp;catcode=cyd&amp;subcatcode=y_arts">Kids Arts &amp; Dance</a></li> <li><a href="https://ygtcprod2.personifycloud.com/personifyebusiness/Default.aspx?TabId=177&amp;catcode=cyd&amp;subcatcode=parent_child&amp;subcatcode=swim_sports_play">Kids Sports</a></li> <li><a href="https://ygtcprod2.personifycloud.com/personifyebusiness/Default.aspx?TabId=177&amp;catcode=school_age_care" title="Find School Age Care Programs">School Age Care</a></li> <li><a href="https://ygtcprod2.personifycloud.com/personifyebusiness/Default.aspx?TabId=177&amp;catcode=school_release" title="School Release Days">School Release Days</a></li> <li><a href="/child_care__preschool/summer_programs/" title="Find Summer Programs">Summer Programs</a></li> <li><a href="https://ygtcprod2.personifycloud.com/personifyebusiness/Default.aspx?TabId=177&amp;catcode=teams" title="Teams and Leagues">Teams and Leagues</a></li> <li><a href="/about/volunteer">Volunteer Opportunities</a></li> </ul> </article> </div> </div> </article> <article class="panel panel-default content-expander" id="component_153751"> <div class="panel-heading"> <div class="panel-title"> <h2 id="component_146029"><a class="accordion-toggle collapsed" data-parent=".panel-group" data-toggle="collapse" href="#collapse153751">PDF Schedules by Location</a></h2> </div> </div> <div class="panel-collapse collapse" id="collapse153751"> <div class="panel-body"> <article class="richtext" id="component_146028"> <p>Pool, gym and swim schedules are available for each Y.</p> <p><a href="/all_y_schedules/pdf_schedules" title="Choose Location">Download your schedule</a></p> </article> </div> </div> </article> </article>';
          if ($entity->hasField($field_name)) {
            $value = $entity->get($field_name)->getValue();
            $columns = [];
            foreach ($field_data['parse_columns'] as $col => $n) {
              $columns[] = str_replace($field_name . '_', '', $col);
            }
            foreach ($columns as $col) {
              foreach ($value as $val) {
                if (isset($val[$col])) {
                  // Replace old links by new ones in each defined field.
                  $content = $val[$col];
                  preg_match_all("/<a href=\"https:\/\/ygtcprod2.personifycloud.com.*<\/a>/", $content, $output_array);
                  if ($output_array[0] == []) {
                    preg_match_all("/^https:\/\/ygtcprod2.personifycloud.com\S+/", $content, $output_array);
                  }
                  foreach ($output_array as $lid => $link) {
                    preg_match_all('/<a\s+href=["\']([^"\']+)["\']/i', implode(',', $link), $links);
                    if ($links[1] == []) {
                      $list_of_links = $output_array[0];
                    }
                    else {
                      $list_of_links = $links[1];
                    }
                    foreach ($list_of_links as $old_link) {
                      if (isset($data['mapping'][$old_link])) {
                        $new_link = $data['mapping'][$old_link];
                        $content = str_replace($old_link, $new_link, $content);
                        $used_links[$old_link] = $new_link;
                        $count++;
                      }
                      else {
                        $not_found_links[] = $old_link;
                      }
                    }
                  }
                }
              }
            }
            $entity->set($field_name, $value);
            $entities_to_save[] = $entity;
          }
        }

      }
    }
    drupal_set_message(t('@count links have been successfully updated.', ['@count' => $count]));
    if (isset($used_links)) {
      $unused_links = array_diff($data['mapping'], $used_links);
      $unused_links = implode(' ', $unused_links);
      if (!empty($unused_links)) {
        drupal_set_message(t('Next links were not used for replacing: @links', ['@links' => $unused_links]));
      }
    }
    if (isset($not_found_links)) {
      $not_found_links = implode(' ', $not_found_links);
      drupal_set_message(t('New links were not found for : @links', ['@links' => $not_found_links]));
    }
    return $entities_to_save;
  }

  /**
   * Helper function to retrieve links mapping from csv file.
   */
  protected function prepareLinksArray($csvFile) {
    $paths = $mapping = [];
    foreach ($csvFile as $line) {
      // Read line as comma-separated.
      $line = str_getcsv($line);
      // Explode lines by ; for detecting url pieces.
      $old_line = explode(';', $line[0]);
      $new_line = explode(';', $line[1]);
      // Get internal path.
      $path = str_replace($this::BASE_PATH, '', $old_line[0]);
      // Remove unnecessary url pieces and implode back.
      unset($old_line[0]);
      unset($new_line[0]);
      $old_line = implode(';', $old_line);
      $new_line = implode(';', $new_line);
      // Remove unnecessary quotes.
      $old_line = ltrim($old_line, '"');
      $new_line = ltrim($new_line, '"');
      $old_line = rtrim($old_line, '"');
      $new_line = rtrim($new_line, '"');

      $paths[$path] = $path;
      $mapping[$old_line] = $new_line;
    }
    // Normalize paths for better handling later.
    $paths = $this->normalizePaths($paths);
    // Collect data.
    $data = [
      'paths' => $paths,
      'mapping' => $mapping,
    ];
    return $data;
  }

  /**
   * Helper function to retrieve internal paths.
   */
  protected function normalizePaths($paths) {
    $normalized_paths = [];
    $types = ['block', 'node', 'redirect'];
    foreach ($paths as $path) {
      $parts = explode('/', $path);
      $id = null;
      foreach ($parts as $part) {
        if (is_numeric($part)) {
          $id = $part;
        }
      }
      foreach ($parts as $part) {
        if (in_array($part, $types) && !is_null($id)) {
          if ($part == 'block') {
            $part = 'block_content';
          }
          $normalized_paths[$part][] = $id;
        }
      }
    }
    return $normalized_paths;
  }

  /**
   * Finish callback for batch process.
   */
  function finishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        $results,
        'One post processed.', '@count posts processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}

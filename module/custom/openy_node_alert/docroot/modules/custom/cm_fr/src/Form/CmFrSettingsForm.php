<?php

namespace Drupal\cm_fr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Settings form.
 */
class CmFrSettingsForm extends ConfigFormBase implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cm_fr_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Download CSV');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cm_fr.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('cm_fr.settings');
    $data = array_shift($config->get('tables_whitelist'));

    $db = \Drupal::database();
    foreach ($data as $tname => $tdata) {
      $query = $db->select($tname, 's');
      $fields = array_merge(array_keys($tdata['parse_columns']), [$tdata['id']]);
      $query->fields('s', $fields);
      $conditions = $query->orConditionGroup();
      foreach (array_keys($tdata['parse_columns']) as $cname) {
        $conditions->condition('s.' . $cname, '%https://ygtcprod2.personifycloud.com%', 'LIKE');
      }
      $query->condition($conditions);
      $list = $query->execute()->fetchAll();

      foreach ($list as $id => $finds) {
        foreach (array_keys($tdata['parse_columns']) as $parsed_name) {
          preg_match_all("/<a href=\"https:\/\/ygtcprod2.personifycloud.com.*<\/a>/", $finds->{$parsed_name}, $output_array);
          if ($output_array[0] == []) {
            preg_match_all("/^https:\/\/ygtcprod2.personifycloud.com\S+/", $finds->{$parsed_name}, $output_array);
          }
          foreach ($output_array as $lid => $link) {
            preg_match_all('/<a\s+href=["\']([^"\']+)["\']/i', implode(',', $link), $links);
            if ($links[1] == []) {
              $list_of_links = $output_array[0];
            }
            else {
              $list_of_links = $links[1];
            }

            foreach ($list_of_links as $lexport) {
              $csv[] = [
                'https://www.ymcamn.org/' . str_replace('$id', $finds->{$tdata['id']}, $tdata['edit_pattern']),
                $lexport
              ];
            }
          }
        }

      }
    }

    $f = fopen('php://memory', 'w');
    foreach ($csv as $line) {
      // Generate csv lines from the inner arrays.
      fputcsv($f, $line, ';');
    }
    $filename = "export.csv";
    fseek($f, 0);
    // Tell the browser it's going to be a csv file.
    header('Content-Type: application/csv');
    // Tell the browser we want to save it instead of displaying it.
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    // Make php send the generated csv lines to the browser.
    fpassthru($f);
    fclose($f);

    parent::submitForm($form, $form_state);
  }

}

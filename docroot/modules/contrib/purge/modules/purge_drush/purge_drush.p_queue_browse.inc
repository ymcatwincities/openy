<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;

/**
 * Inspect what is in the queue by paging through it.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purgeQueue
 *   The purge queue service.
 * @param string|int $limit
 *   The number of items to show on a single page.
 * @param string|int $page
 *   The page to show data for, pages start at 1.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_queue_browse($format, QueueServiceInterface $purgeQueue, $limit, $page) {
  $purgeQueue->selectPageLimit(intval($limit));

  // Make the output pageable and interactive for first-page table renders.
  if (($format === 'table') && ($page === 1)) {
    for ($page = 1; $page <= ($max = $purgeQueue->selectPageMax()); $page++) {

      // Let the renderer render our table, then buffer and reprint it.
      ob_start();
      drush_handle_command_output(
        drush_get_command(),
        purge_drush_p_queue_browse($format, $purgeQueue, $limit, "$page")
      );
      $output = ob_get_contents();
      ob_end_clean();
      $lines = explode("\n", $output);
      foreach ($lines as $line => $content) {
        if ((!strlen($content)) || (($line === 0) && ($page !== 1))) {
          unset($lines[$line]);
        }
      }
      print(implode("\n", $lines) . "\n");

      // Read input to let users stop or continue the process.
      if ($page != $max) {
        $msg = sprintf("[%d/%d] Press any key for more, [q] to quit...", $page, $max);
        $term = shell_exec("stty -g");
        print($msg);
        system("stty -icanon");
        while ($c = fread(STDIN, 1)) {
          print chr(8);
          if ($c === 'q') {
            system(sprintf("stty %s", $term));
            print("\n");
            return [];
          }
          system(sprintf("stty %s", $term));
          print(str_repeat(chr(8), strlen($msg)));
          break;
        }
      }
    }

    return [];
  }

  // The normal non-interactive code-path that just returns structured data.
  $output = [];
  foreach ($purgeQueue->selectPage(intval($page)) as $immutable) {
    if ($format === 'list') {
      $output[] = $immutable->getExpression();
    }
    else {
      $output[] = [
        'type' => $immutable->getType(),
        'typetr' => (string) $immutable->getPluginDefinition()['label'],
        'state' => $immutable->getStateString(),
        'statetr' => (string) $immutable->getStateStringTranslated(),
        'expression' => $immutable->getExpression(),
      ];
    }
  }
  return $output;
}

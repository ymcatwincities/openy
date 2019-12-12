<?php

namespace Drupal\openy_repeat\Batch;

/**
 * Runs a single repeat instance actualization batch.
 */
class RepeatActualizeBatch {

    /**
     * Batch process repeat instance actualization.
     *
     * @param $session_ids array
     *   Session IDs array.
     * @param $context array
     *   Context array.
     */
    public static function run($session_ids, &$context) {
        if (empty($context['sandbox'])) {
            // Perform set-up steps here.
            $context['sandbox']['progress'] = 0;
            $context['sandbox']['max'] = count($session_ids);
        }
        $items_per_iteration = 1;

        $nids = array_slice($session_ids, $context['sandbox']['progress'], $items_per_iteration);
        $sessions = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadMultiple($nids);
        $session_instance_manager = \Drupal::service('session_instance.manager');
        foreach ($sessions as $session) {
            $session_instance_manager->recreateSessionInstances($session);
            $context['sandbox']['progress']++;
            $context['results'][] = $session->id();
        }

        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }

    /**
     * Batch finalization sessions instance actualization.
     *
     * @param $success bool
     *   A boolean indicating whether the batch has completed successfully.
     * @param $results array
     *   The value set in $context['results'] by callback_batch_operation().
     * @param $operations array
     *   If $success is FALSE, contains the operations that remained unprocessed.
     */
    public static function finished($success, $results, $operations) {
        if ($success) {
            $message = t('@count sessions were processed.', ['@count' => count($results)]);
            drupal_set_message($message);
        }
        else {
            // An error occurred.
            $error_operation = reset($operations);
            $message = t('An error occurred while processing %error_operation with arguments: @arguments',
                array(
                    '%error_operation' => $error_operation[0],
                    '@arguments' => print_r($error_operation[1], TRUE)
                ));
            drupal_set_message($message, 'error');
        }
    }

}

<?php

namespace Drupal\ldbase_new_account;

use Drupal\node\Entity\Node;

/**
 * Class BatchService.
 */
class BatchService {
  /**
   * Batch process callback.
   *
   * @param int $id
   *  Id of the batch.
   * @param int $nid
   *  ID of the person node to check
   * @param string $operation_details
   *  Details of the operation.
   * @param object $context
   *  Context for operations.
   */
  public static function collectMatches($id, $nid, $operation_details, &$context) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $helper_service = \Drupal::service('ldbase_new_account_service.helper');

    // search for match
    // name on non-user Person node
    $person = $node_storage->load($nid);
    $index = \Drupal\search_api\Entity\Index::load('default_index');
    $query = $index->query();

    // Change the parse mode for the search.
    $parse_mode = \Drupal::service('plugin.manager.search_api.parse_mode')->createInstance('direct');
    $parse_mode->setConjunction('OR');
    $query->setParseMode($parse_mode);
    //$query->setProcessingLevel(0);

    // Set fulltext search keywords and fields
    $query->keys($person->getTitle());
    $query->setFullTextFields(['person_name_fields_united', 'field_publishing_names', 'title']);
    $results = $query->execute();
    // if there are results
    if (!empty($results)) {
      $results_items = $results->getResultItems();
      foreach ($results_items as $item) {
        $rest = substr($item->getId(), 12);
        $pieces = explode(':', $rest);
        if($pieces[0] != "") {
          $item_id = $pieces[0];
        }
        // make sure this result is not just the original node used to search
        if ($item_id != $nid) {
          // make sure this person has a user attached
          $check_user = $node_storage->load($item_id);
          $user_id = $check_user->hasField('field_drupal_account_id') ? $check_user->get('field_drupal_account_id')->target_id : null;
          if (!empty($user_id)) {
            // get referenced content
            $content = $helper_service->retrieveContentByPersonId($nid);
            if (!empty($content)) {
              // check that we haven't stored this possibility already
              foreach ($content as $content_id) {
                $existing_match = $node_storage->getQuery()
                ->accessCheck(TRUE)
                ->condition('type','possible_user_match')
                ->condition('field_possible_match_person_id', $nid)
                ->condition('field_real_person_id', $item_id)
                ->condition('field_content_id', $content_id)
                ->execute();
                if (empty($existing_match)) {
                  // add possible match node
                  $new_match = $node_storage->create([
                    'type' => 'possible_user_match',
                    'status' => 1,
                    'title' => $person->getTitle() . ' possible match',
                    'field_possible_match_person_id' => $nid,
                    'field_real_person_id' => $item_id,
                    'field_content_id' => $content_id,
                    'field_user_match_status' => 'new',
                  ]);
                  $new_match->save();

                  // message user of possible match $user_id
                  \Drupal::service('ldbase_handlers.message_service')->possibleMatchesNotification($user_id);
                }
                // check if the existing match is more than $update_horizon old
                else {
                  $update_horizon = strtotime('-30 days');  // 30 days ago
                  foreach ($existing_match as $match_id) {
                    $match_node = $node_storage->load($match_id);
                    $match_node_updated = $match_node->changed->value;
                    $match_node_status = $match_node->get('field_user_match_status')->value;

                    if ($match_node_status == 'new' && $update_horizon >= $match_node_updated) {
                      $match_node->setChangedTime(\Drupal::time()->getRequestTime());
                      $match_node->save();

                      // message user of possible match $user_id
                      \Drupal::service('ldbase_handlers.message_service')
                        ->possibleMatchesNotification($user_id);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    $context['results'][] = $id;
    // Optional message displayed under the progressbar.
    $context['message'] = t('Running Batch "@id" @details',
      ['@id' => $id, '@details' => $operation_details]
    );
  }

  /**
   * Batch Finished Callback.
   *
   * @param bool $success
   *  Success of the operation.
   * @param array $results
   *  Array of results for post processing.
   * @param array $operations
   *  Array of operations
   */
  public function collectMatchesFinished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of nodes we processed...
      $messenger->addMessage(t('@count records checked.', ['@count' => count($results)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  private function getNidFromSearchId($search_id) {
    $result = null;
    if(substr($id, 0, 12) == 'entity:node/') {
      $rest = substr($id, 12);
      $pieces = explode(':', $rest);
      if($pieces[0] != "") {
        $result = $pieces[0];
      }
    }
    return $result;
  }
}

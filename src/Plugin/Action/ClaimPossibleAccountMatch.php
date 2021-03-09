<?php

namespace Drupal\ldbase_new_account\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Claim a Possible Account Match.
 *
 * @Action(
 *   id = "claim_possible_account_match",
 *   label = @Translation("LDbase - Claim a Possible Account Match"),
 *   type = "node"
 * )
 */
class ClaimPossibleAccountMatch extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($match = NULL) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $request_id = time();

    $previous_person_id = $match->field_possible_match_person_id->target_id;
    $real_person_id = $match->field_real_person_id->target_id;

    $content_id = $match->field_content_id->target_id;
    $content_author_id = $node_storage->load($content_id)->getOwnerId();

    $new_existing_record_request = $node_storage->create([
      'type' => 'existing_record_request',
      'status' => true,
      'title' => $request_id,
      'field_previous_person' => $previous_person_id,
      'field_requesting_person' => $real_person_id,
      'field_requested_node_link' => $content_id,
      'field_node_owner' => $content_author_id,
      'field_request_status' => 'New',
    ]);
    $new_existing_record_request->save();
    $new_request_id = $new_existing_record_request->id();

    // set match status
    $match->set('field_user_match_status','submitted');
    $match->save();

    // notify Project Administrators of requests
    // pass new request id
    \Drupal::service('ldbase_handlers.message_service')->existingRecordRequestMade($new_request_id);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return true;
  }

}

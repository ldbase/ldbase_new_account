<?php

namespace Drupal\ldbase_new_account\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Approve an Existing Record Request.
 *
 * @Action(
 *   id = "approve_existing_record_request",
 *   label = @Translation("LDbase - Approve Existing Record Request"),
 *   type = "node"
 * )
 */
class ApproveExistingRecordRequest extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($request = NULL) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $use_this_person = $request->field_requesting_person->target_id;
    $replace_this_person = $request->field_previous_person->target_id;
    $node_id = $request->field_requested_node_link->target_id;
    $on_this_node = $node_storage->load($node_id);
    // get the authors/contributors for the node
    $field_related_persons = $on_this_node->get('field_related_persons')->getValue();
    // loop over authors and remove $replace_this_person
    foreach ($field_related_persons as $key => $value) {
      if ($value["target_id"] == $replace_this_person) {
        unset($field_related_persons[$key]);
      }
    }
    // add $use_this_person
    $field_related_persons[] = ["target_id" => $use_this_person];
    $reindexed_array = array_values($field_related_persons);
    // save changes
    $on_this_node->set('field_related_persons', $reindexed_array);
    $on_this_node->save();

    //check if $replace_this_person is still referenced
    $replaced_person_query = $node_storage->getQuery()
      ->condition('field_related_persons', $replace_this_person)
      ->execute();
    // if not delete $replace_this_person
    if (empty($replaced_person_query)) {
      $unattached_person = $node_storage->load($replace_this_person);
      $unattached_person->delete();
    }

    $request->set('field_request_status','Approved');
    $request->save();

    $redirect_message = 'Approvals have been successfully processed.  Those records have been connected to the requesting user accounts.';
    $this->messenger()->addStatus($this->t($redirect_message));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // return $account->hasPermission('overview messages');
    return TRUE;
  }

}

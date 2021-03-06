<?php

namespace Drupal\ldbase_new_account\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Reject an Existing Record Request.
 *
 * @Action(
 *   id = "reject_existing_record_request",
 *   label = @Translation("LDbase - Reject Existing Record Request"),
 *   type = "node"
 * )
 */
class RejectExistingRecordRequest extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($request = NULL) {
    $request->set('field_request_status','Rejected');
    $request->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return true;
  }

}

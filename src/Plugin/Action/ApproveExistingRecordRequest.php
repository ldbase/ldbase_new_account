<?php

namespace Drupal\ldbase_new_account\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Mark message as Read.
 *
 * @Action(
 *   id = "approve_existing_record_request",
 *   label = @Translation("Approve Existing Record Request"),
 *   type = "node"
 * )
 */
class ApproveExistingRecordRequest extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($request = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // return $account->hasPermission('overview messages');
    return TRUE;
  }

}

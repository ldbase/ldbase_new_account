<?php

namespace Drupal\ldbase_new_account\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Reject a Possible Account Match.
 *
 * @Action(
 *   id = "reject_possible_account_match",
 *   label = @Translation("LDbase - Reject a Possible Account Match"),
 *   type = "node"
 * )
 */
class RejectPossibleAccountMatch extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($match = NULL) {
    // set match status
    $match->set('field_user_match_status','rejected');
    $match->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return true;
  }

}

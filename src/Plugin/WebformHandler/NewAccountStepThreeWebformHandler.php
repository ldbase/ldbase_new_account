<?php

namespace Drupal\ldbase_new_account\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\user\Entity\User;

/**
 * Handle the submission of the account creation webform (step 3).
 *
 * @WebformHandler(
 *   id = "new_account_step_three_webform",
 *   label = @Translation("LDbase New Account Step Three"),
 *   category = @Translation("Content"),
 *   description = @Translation("Asks for a password and creates the new person and user."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

class NewAccountStepThreeWebformHandler extends WebformHandlerBase {

  /**
    * {@inheritdoc}
    */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // Get the submitted form values
    $submission_array = $webform_submission->getData();

    // Retrieve the data from step two from session temporary storage
    // NOTE: You have to do this before login the newly created user below,
    // as that function creates a new session for the logged in user
    $tempstore = \Drupal::service('tempstore.private')->get('ldbase_new_account');
    $match_nids = $tempstore->get('match_nids');

    // Create Drupal User
    $user = User::create();

    //Mandatory settings
    $user->setPassword($submission_array['ldbase_password']);
    $user->enforceIsNew();
    $user->setEmail($submission_array['primary_email']);
    $user->setUsername($submission_array['primary_email']);
    $user->activate();

    // Save the user
    $user->save();

    // Create the person
    $person_title = $submission_array['preferred_display_name'];
    $person_first_name = $submission_array['ldbase_primary_name'][0]['primary_first_name'];
    $person_middle_name = $submission_array['ldbase_primary_name'][0]['primary_middle_name'];
    $person_last_name = $submission_array['ldbase_primary_name'][0]['primary_last_name'];
    $person_email = $submission_array['primary_email'];

    $person_node = Node::create([
      'type' => 'person',
      'status' => TRUE, // published
      'title' => $person_title,
      'field_first_name' => $person_first_name,
      'field_middle_name' => $person_middle_name,
      'field_last_name' => $person_last_name,
      'field_email' => $person_email,
      'field_drupal_account_id' => $user->id(),
      'uid' => $user->id(), // set author to be this user
    ]);

    // Save the person
    $person_node->save();

    // Pass the node IDs to the helper service that stores and messages users
    if (count($match_nids) > 0) {
      $helper_service = \Drupal::service('ldbase_new_account_service.helper');
      $helper_service->storeExistingRecordsRequest($match_nids, $person_node->id());
    }

    //log the user in
    user_login_finalize($user);

    // Set the message to display in the next page
    $form_state->set('redirect_message', 'Your account has been successfully created.');
    $form_state->set('user_redirect', $user->id());
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // redirect to user account page
    $route_name = 'entity.user.canonical';
    $route_parameters = ['user' => $form_state->get('user_redirect')];
    $this->messenger()->addStatus($this->t($form_state->get('redirect_message')));

    $form_state->setRedirect($route_name, $route_parameters);
  }

 }

<?php

namespace Drupal\ldbase_new_account\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Handle the submission of the account creation webform (step one).
 *
 * @WebformHandler(
 *   id = "new_account_step_one_webform",
 *   label = @Translation("LDbase New Account Step One"),
 *   category = @Translation("Content"),
 *   description = @Translation("Searches for existing records and decides next step."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

class NewAccountStepOneWebformHandler extends WebformHandlerBase {

  /**
    * {@inheritdoc}
    */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
      
    // Get the submitted form values
    $submission_array = $webform_submission->getData();
    
    // Use the submitted values to find existing records
    $helper_service = \Drupal::service('ldbase_new_account_service.helper');
    $possible_match_item_ids = $helper_service->personSearch($submission_array);
  
    // Set next step
    if(count($possible_match_item_ids)) {
      $form_state->set('next_step', 2);
      $form_state->set('redirect_message', 'Found ' . count($possible_match_item_ids) . ' records with the information provided.');
    } else {
      $form_state->set('next_step', 3);
      $form_state->set('redirect_message', 'No records were found with the information provided.');
    }
    
    // Store the data in session temporary storage
    $tempstore = \Drupal::service('tempstore.private')->get('ldbase_new_account');
    $tempstore->set('step_one_submission_data', $submission_array);
    $tempstore->set('possible_match_item_ids', $possible_match_item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $next_step = $form_state->get('next_step');
    
    if ($next_step == 2) {
      $route_name = 'ldbase_new_account.step_two';
    } else {
      $route_name = 'ldbase_new_account.step_three'; 
    }
    
    $route_parameters = array();
    $this->messenger()->addStatus($this->t($form_state->get('redirect_message')));
    $form_state->setRedirect($route_name, $route_parameters);
  }  
}
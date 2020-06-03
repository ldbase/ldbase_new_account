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
    
    // Store the data in session temporary storage
    $tempstore = \Drupal::service('tempstore.private')->get('ldbase_new_account');
    $tempstore->set('step_one_submission_data', $submission_array);
    
    // Set the message to display in the next page
    $form_state->set('redirect_message', 'No records were found with the information provided.');
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // redirect to step three
    $route_name = 'ldbase_new_account.step_three';
    $route_parameters = array();
    $this->messenger()->addStatus($this->t($form_state->get('redirect_message')));

    $form_state->setRedirect($route_name, $route_parameters);
  }

 }
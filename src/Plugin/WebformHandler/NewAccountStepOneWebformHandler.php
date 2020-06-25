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
    $possible_match_item_ids = $this->personSearch($submission_array);
  
// Store the data in session temporary storage
    $tempstore = \Drupal::service('tempstore.private')->get('ldbase_new_account');
    $tempstore->set('step_one_submission_data', $submission_array);
    
    // Set the message to display in the next page
    $form_state->set('redirect_message', 'Found ' . count($possible_match_item_ids) . ' records with the information provided.');
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
  
  private function personSearch(array $submission_array) {   
    $primary_first_name = $submission_array['ldbase_primary_name'][0]['primary_first_name'];
    $primary_middle_name = $submission_array['ldbase_primary_name'][0]['primary_middle_name'];
    $primary_last_name = $submission_array['ldbase_primary_name'][0]['primary_last_name'];
  
    $possible_matches = $this->nameSearch($primary_first_name, $primary_middle_name, $primary_last_name);
    return $possible_matches;
  }
  
  private function nameSearch($first_name, $middle_name, $last_name) {
    $all_results = array();
    $possible_matches = array();
      
    $first_name_results = $this->personNameQuery($first_name);
    $middle_name_results = $this->personNameQuery($middle_name);
    $last_name_results = $this->personNameQuery($last_name);
    
    if($first_name_results->getResultCount() > 0){
      foreach($first_name_results->getResultItems() as $result) {
        $all_results[] = $result->getId();
      }
    }

    if($middle_name_results->getResultCount() > 0){
      foreach($middle_name_results->getResultItems() as $result) {
        if (in_array($result->getId(), $all_results)) {
          $possible_matches[] = $result->getId(); 
        } else {
          $all_results[] = $result->getId();  
        }
      }
    }
    
    if($last_name_results->getResultCount() > 0){
      foreach($last_name_results->getResultItems() as $result) {
        if (in_array($result->getId(), $all_results)) {
          $possible_matches[] = $result->getId(); 
        }
      }
    }
    
    return $possible_matches;
  }
  
  private function personNameQuery($keyword) {      
    $index = \Drupal\search_api\Entity\Index::load('default_index');
    $query = $index->query();
    
    // Change the parse mode for the search.
    $parse_mode = \Drupal::service('plugin.manager.search_api.parse_mode')->createInstance('direct');
    $parse_mode->setConjunction('OR');
    $query->setParseMode($parse_mode);
    
    // Set fulltext search keywords and fields
    $query->keys($keyword);
    $query->setFullTextFields(['person_name_fields_united', 'title']);
    
    // If keyword is empty, make it return an empty result set
    if (empty($keyword)) {
      $query->setOption('limit', 0);
    } 
    
    // Execute the Search and return the results
    $results = $query->execute();
    return $results;
  }  
}
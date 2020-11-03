<?php

namespace Drupal\ldbase_new_account\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;

/**
 * Handle the submission of the account creation webform.
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
    $email_address = $submission_array['primary_email'];
      
    // Check to see if the email address has already been used
    $ids = \Drupal::entityQuery('user')
           ->condition('name', $email_address)
           ->range(0, 1)
           ->execute();
    
    // If the email address has already been used, send them to password reset screen
    if(!empty($ids)){
      $form_state->set('redirect', 'user.page');
      $form_state->set('redirect_message', 'The email address you entered already exists.');
      $form_state->set('message_type', 'error');
      $form_state->set('user_redirect', '');
    }
    else {// If it hasn't been used, create the account
    
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
      $additional_names = $submission_array['ldbase_additional_names'];
      
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
    
      foreach($additional_names as $additional_name) {
        $name_to_add = $additional_name['additional_first_name'] . ' ' . $additional_name['additional_middle_name']
                . ' ' . $additional_name['additional_last_name'];
        $person_node->field_publishing_names->appendItem($name_to_add);
      }
      
      // Save the person
      $person_node->save();

      //log the user in
      user_login_finalize($user);
      
      // Set the message and route for next page
      $form_state->set('redirect_message', 'Your account has been successfully created.');
      $form_state->set('message_type', 'normal');
      $form_state->set('redirect', 'entity.user.canonical');
      $form_state->set('user_redirect', $user->id());
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // Set message for next screen
    if($form_state->get('message_type') == 'normal') {
      $this->messenger()->addStatus($this->t($form_state->get('redirect_message')));
    } else {
      $this->messenger()->addError($this->t($form_state->get('redirect_message')));
    }
    
    // redirect based on submit data
    $route_parameters = ['user' => $form_state->get('user_redirect')];
    $route_name = $form_state->get('redirect');
    $form_state->setRedirect($route_name, $route_parameters);
  }
}
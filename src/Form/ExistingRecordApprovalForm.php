<?php

namespace Drupal\ldbase_new_account\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class ExistingRecordApprovalForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  
  public function getFormId() {
    return 'ldbase_new_account_existing_record_approval_form';
  }
  
  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) { 
    $helper_service = \Drupal::service('ldbase_new_account_service.helper');
      
    // Retrieve existing record requests owned by this user
    $existing_record_request_ids = $helper_service->retrieveExistingRecordsRequestByOwner();
    
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('The following individual(s) recently created an account on LDbase and believe they were'
              . ' collaborators in the following records. As owner of the record, do you approve for their accounts to be linked'
              . ' as collaborators? (This action does not confer any additional permissions to this user)'),
    ];
    
    $header = [
      'requestor' => t('Requestor'),
      'content_title' => t('Content Title')
    ];

    $options = array();
      
    foreach ($existing_record_request_ids as $node_id) {
      $existing_record_request = Node::load($node_id);
      $node_to_be_linked_id = $existing_record_request->field_requested_node_link->getValue(); 
      $node_to_be_linked = Node::load($node_to_be_linked_id[0]['target_id']);
      
      $options[$existing_record_request->id()] = [
        'requestor' => $existing_record_request->bundle(),
        'content_title' => $node_to_be_linked->getTitle(),
      ];
    }
      
    $form['existing_record_requests_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('No data found.'),
    ];
    
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }
  
    /**
   * Form Validation
   * 
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * 
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }
  
  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  } 
}

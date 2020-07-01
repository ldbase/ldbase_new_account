<?php

namespace Drupal\ldbase_new_account\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class StepTwoForm extends FormBase {
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
    return 'ldbase_new_account_step_two_form';
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
      
    // Retrieve the data from step one from session temporary storage
    $tempstore = \Drupal::service('tempstore.private')->get('ldbase_new_account');
    $possible_match_item_ids = $tempstore->get('possible_match_item_ids');
    
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Please review the records below and check any existing records where you have been identified as a contributor.'),
    ];
    
    $count = 0;
    $helper_service = \Drupal::service('ldbase_new_account_service.helper');
    
    foreach($possible_match_item_ids as $possible_match) {
      $person_node = $helper_service->nodeFromItemId($possible_match);
      $form['person-nid_' . $person_node->id()] = array(
        '#type' => 'checkbox',
        '#title' => $person_node->getTitle(),
      );
      
      $content = $helper_service->retrieveContentByPersonId($person_node->id());
      
      $header = [
        'type' => t('Content Type'),
        'title' => t('Content Title')
      ];
      
      $values = array();
      
      foreach ($content as $node_id) {
        $node = Node::load($node_id);  
        
        $values[$node->id()] = [
          'type' => $node->bundle(),
          'title' => $node->getTitle(),
        ];
      }
      
      $person_content_index = 'person_content_match_' . $count;
      
      $form[$person_content_index] = [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $values,
          '#empty' => t('No data found.'),
      ];
      
      $count++;
    }
    
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
    // Store checked matches
    $match_nids = array();
    $values = $form_state->getValues();
    
    foreach($values as $key => $value){
      if((substr($key, 0, 11) === 'person-nid_') && ($value == 1)) {
        $match_data = explode('_', $key);
        $match_nids[] = $match_data[1];
      }
    }
    
    // Store the data in session temporary storage
    $tempstore = \Drupal::service('tempstore.private')->get('ldbase_new_account');
    $tempstore->set('match_nids', $match_nids);
    
    if (count($match_nids) > 0) {
      $redirect_message = "The owners of the records you identified will be notified for approval.";  
    } else {
      $redirect_message = "No records were selected. Continuing account creation.";
    }
    
    // Redirect to third step
    $route_parameters = array();
    $this->messenger()->addStatus($this->t($redirect_message));
    $form_state->setRedirect('ldbase_new_account.step_three', $route_parameters);
  } 
}

<?php

namespace Drupal\ldbase_new_account\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
    
    foreach($possible_match_item_ids as $possible_match) {
      $form['match_' . $count] = array(
        '#type' => 'checkbox',
        '#title' => $this->t($possible_match),
        '#description' => $this->t($possible_match),
      );
      
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
    // Redirect to home
    $form_state->setRedirect('<front>');
  } 
}

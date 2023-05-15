<?php

namespace Drupal\ldbase_new_account\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Link;
use Drupal\Core\Url;

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
      'email' => t('Email'),
      'content_title' => t('Content Title')
    ];

    $options = array();

    foreach ($existing_record_request_ids as $node_id) {
      $existing_record_request = Node::load($node_id);

      // Create a link to the user requesting the new link
      $person_to_be_linked_field = $existing_record_request->field_requesting_person->getValue();
      $person_to_be_linked_id = $person_to_be_linked_field[0]['target_id'];
      $person_to_be_linked = Node::load($person_to_be_linked_id);
      $person_email_field = $person_to_be_linked->field_email->getValue();
      $person_email = $person_email_field[0]['value'];
      $person_link = Link::fromTextAndUrl($person_to_be_linked->getTitle(),
        Url::fromRoute('entity.node.canonical', ['node' => $person_to_be_linked_id]));

      // Create a link to the content where collaborator is being added
      $node_to_be_linked_field = $existing_record_request->field_requested_node_link->getValue();
      $node_to_be_linked_id = $node_to_be_linked_field[0]['target_id'];
      $node_to_be_linked = Node::load($node_to_be_linked_id);
      $content_link = Link::fromTextAndUrl($node_to_be_linked->getTitle(),
        Url::fromRoute('entity.node.canonical', ['node' => $node_to_be_linked_id]));

      $options[$existing_record_request->id()] = [
        'requestor' => $person_link,
        'email' => $person_email,
        'content_title' => $content_link,
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
    // Retrieve the submitted values
    $values = $form_state->getValues();
    foreach($values['existing_record_requests_table'] as $existing_record_request_id) {
      $existing_record_request = Node::load($existing_record_request_id);

      // Retrieve the content that needs to be updated
      $node_to_be_linked_field = $existing_record_request->field_requested_node_link->getValue();
      $node_to_be_linked_id = $node_to_be_linked_field[0]['target_id'];

      // Retrieve the id of the person to be linked
      $person_to_be_linked_field = $existing_record_request->field_requesting_person->getValue();
      $person_to_be_linked_id = $person_to_be_linked_field[0]['target_id'];

      // Retrieve the id of the person to be removed
      $person_to_be_removed_field = $existing_record_request->field_previous_person->getValue();
      $person_to_be_removed_id = $person_to_be_removed_field[0]['target_id'];

      // Remove the previous person from the node
      $node_to_be_linked = Node::load($node_to_be_linked_id);
      $related_persons = $node_to_be_linked->field_related_persons->getValue();
      foreach($related_persons as $key => $related_person) {
        if($related_person['target_id'] == $person_to_be_removed_id){
          $node_to_be_linked->field_related_persons->removeItem($key);
        }
      }
      // Link the person to the node
      $node_to_be_linked->field_related_persons[] = ['target_id' => $person_to_be_linked_id];

      // Save the node and delete the request
      $node_to_be_linked->save();
      $existing_record_request->delete();
    }

    // Set the message to display in the next page
    if (count($values['existing_record_requests_table']) > 0) {
      $redirect_message = 'Approvals have been successfully processed.';
    } else {
      $redirect_message = 'No approvals were processed.';
    }

    // redirect to user dashboard
    $route_name = 'entity.user.canonical';
    $route_parameters = ['user' => \Drupal::currentUser()->id()];
    $this->messenger()->addStatus($this->t($redirect_message));

    $form_state->setRedirect($route_name, $route_parameters);
  }
}

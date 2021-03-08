<?php

namespace Drupal\ldbase_new_account\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class PossibleAccountMatches extends FormBase {
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
    return 'ldbase_new_account_possible_account_matches_form';
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
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $current_user_id = \Drupal::currentUser()->id();
    // get the person node attached to this user
    $real_person_id = $node_storage->getQuery()
      ->condition('type','person')
      ->condition('field_drupal_account_id', $current_user_id)
      ->execute();
    // Retrieve the possible user match nids for this person
    $possible_user_matches = $node_storage->getQuery()
      ->condition('type','possible_user_match')
      ->condition('field_real_person_id', key($real_person_id))
      ->execute();

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Please review the records below and check any existing records where you have been identified as a contributor.'),
    ];

    $count = 0;
    $helper_service = \Drupal::service('ldbase_new_account_service.helper');

    // loop over possible user match nids
    foreach ($possible_user_matches as $match_id) {
      // get the possible person ids
      $possible_person_id = $node_storage->load($match_id)->get('field_possible_match_person_id')->target_id;
      $person_node = $node_storage->load($possible_person_id);

      $content = $helper_service->retrieveContentByPersonId($person_node->id());

      $header = [
        'possible_match' => t('Possible Match'),
        'type' => t('Content Type'),
        'title' => t('Content Title')
      ];

      $options = [];
      foreach ($content as $node_id) {
        $node = Node::load($node_id);
        // checkbox will pass real person id and possible match person id and content node id
        $options[key($real_person_id) . '_' . $person_node->id() . '_' . $node->id()] = [
          'possible_match' => $person_node->getTitle(),
          'type' => ucfirst($node->bundle()),
          'title' => $node->getTitle(),
        ];
      }
    }

    $form['table'] = [
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
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    // Store checked matches
    // checkbox will pass real person id _ possible match person id _ content node id
    $match_nids = array();
    $values = $form_state->getValues();

    foreach($values['table'] as $value){
      if($value) {
        $split_ids = explode('_',$value);
        $real_person_id = $split_ids[0];
        $possible_person_id = $split_ids[1];
        $content_id = $split_ids[2];

        $content_author_id = $node_storage->load($content_id)->getOwnerId();
        $request_id = time();

        $new_existing_record_request = $node_storage->create([
          'type' => 'existing_record_request',
          'status' => true,
          'title' => $request_id,
          'field_previous_person' => $possible_person_id,
          'field_requesting_person' => $real_person_id,
          'field_requested_node_link' => $content_id,
          'field_node_owner' => $content_author_id,
          'field_request_status' => 'New',
        ]);
        $new_existing_record_request->save();
        $new_request_id = $new_existing_record_request->id();

        // notify Project Administrators of requests
        // pass new request id
        //\Drupal::service('ldbase_handlers.message_service')->existingRecordRequestMade($new_request_id);

        $redirect_message = "The Project Administrators of the records you identified will be notified for approval.";
      }
      else {
        $redirect_message = "No records were selected.";
      }
    }

    // redirect to user profile
    $route_name = 'entity.user.canonical';
    $route_parameters = ['user' => \Drupal::currentUser()->id()];
    $this->messenger()->addStatus($this->t($redirect_message));

    $form_state->setRedirect($route_name, $route_parameters);
  }
}

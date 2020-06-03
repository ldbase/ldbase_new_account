<?php

namespace Drupal\ldbase_new_account\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NewAccountController extends ControllerBase {

  /**
   * NewAccountController constructor
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * Loads step one data into the step 3 webform
   */
  public function stepThree() {

    // Retrieve the data from step one from session temporary storage
    $tempstore = \Drupal::service('tempstore.private')->get('ldbase_new_account');
    $step_one_data = $tempstore->get('step_one_submission_data');

    $values = [
      'data' => [
        'ldbase_primary_name' => $step_one_data['ldbase_primary_name'],
        'primary_email' => $step_one_data['primary_email'],
      ]
    ];
    
    $operation = 'add';
    // get organization webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('account_creation_step_3');
    $webform = $webform->getSubmissionForm($values, $operation);

    return $webform;
  }

}
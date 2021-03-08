<?php

namespace Drupal\ldbase_new_account\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ldbase_new_account\Service\LDbaseNewAccountService;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class LDbaseNewAccountCommands extends DrushCommands {
  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;
  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;
  /**
   * LDbase New Account Helper service
   *
   * @var Drupal\ldbase_new_account\Service\LDbaseNewAccountService
   */
  /**
   * Constructs a new LDbaseNewAccountCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger service.
   * @param \Drupal\ldbase_new_account\Service\LDbaseNewAccountService $newAccountService
   *   New Account Service
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory, LDbaseNewAccountService $newAccountService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->newAccountService = $newAccountService;
  }

  /**
   * Get possible matches
   *
   * @command ldbase:possibleAccountMatches
   * @aliases ldbase:pams
   */
  public function possibleAccountMatches() {
    // log process start
    $this->loggerChannelFactory->get('ldbase')->info('Finding Possible Person Matches ...');

    // get person nodes that are not connected to a drupal user
    $node_storage = $this->entityTypeManager->getStorage('node');
    $unconnected_person_ids = $node_storage->getQuery()
      ->condition('type','person')
      ->notExists('field_drupal_account_id')
      ->execute();

    // create operations array for the batch
    $operations = [];
    $numOperations = 0;
    $batchId = 1;
    if (!empty($unconnected_person_ids)) {
      foreach($unconnected_person_ids as $person) {
        $this->output()->writeln("Preparing batch: " . $batchId);
        $operations[] = [
          '\Drupal\ldbase_new_account\BatchService::collectMatches',
          [
            $batchId,
            $person,
            t('Checking node @id', ['@id' => $person]),
          ]
        ];
        $batchId++;
        $numOperations++;
      }
    }
    else {
      $this->logger()->warning('No eligible Person nodes found.');
    }

    // create batch
    $batch = [
      'title' => t('Checking @num node(s) for matches', ['@num' => $numOperations]),
      'operations' => $operations,
      'finished' => '\Drupal\ldbase_new_account\BatchService::collectMatchesFinished',
    ];

    // add batch operations as new batch sets
    batch_set($batch);

    // process batch sets
    drush_backend_batch_process();

    // Show some information.
    $this->logger()->notice("Batch operations end.");
    // Log some information.
    $this->loggerChannelFactory->get('ldbase')->info('Person matching batch operations finished.');

  }
}

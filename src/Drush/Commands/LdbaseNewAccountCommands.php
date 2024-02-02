<?php

namespace Drupal\ldbase_new_account\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class LdbaseNewAccountCommands extends DrushCommands {

  /**
   * Constructs a LdbaseNewAccountCommands object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'ldbase_new_account:possibleAccountMatches', aliases: ['ldbase:pams'])]
  #[CLI\Usage(name: 'ldbase:pams', description: 'Straightforward usage no options or arguments')]
  public function possibleAccountMatches() {
    $this->logger()->info(dt('Finding Possible Person Matches ...'));
    // log process start
    $this->loggerFactory->get('ldbase')->info('Finding Possible Person Matches ...');

    // get person nodes that are not connected to a drupal user
    $node_storage = $this->entityTypeManager->getStorage('node');
    $unconnected_person_ids = $node_storage->getQuery()
      ->accessCheck(TRUE)
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
    $this->logger()->notice("Person matching batch operations end.");
    // Log some information.
    $this->loggerFactory->get('ldbase')->info('Person matching batch operations finished.');
  }

}

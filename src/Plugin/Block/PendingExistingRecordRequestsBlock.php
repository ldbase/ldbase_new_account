<?php

namespace Drupal\ldbase_new_account\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\Group\GroupMembershipLoader;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function PHPUnit\Framework\isInstanceOf;

/**
 * Provides a block that notifies project managers that there are pending existing records requests in listed projects.
 *
 * @Block(
 *   id = "pending_existing_records_requests",
 *   admin_label = @Translation("Custom Pending Existing Records Requests"),
 *   category = @Translation("LDbase Block")
 * )
 */

class PendingExistingRecordRequestsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * An entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The Groups Membership Loader service
   *
   * @var \Drupal\Group\GroupMembershipLoader
   */
  protected $groupMembershipLoader;

  /**
   * @inheritDoc
   */
  public function build() {
    // Is current user a Project Manager in any groups?
    $uid = $this->currentUser->id();
    $user_entity = $this->entityTypeManager->getStorage('user')->load($uid);
    $managed_groups = $this->getProjectManagerGroups($user_entity);
    $link_route = 'view.existing_records_requests.page_1';
    if (!empty(($managed_groups))) {
      $links_to_review = [];
      $nodes = [];
      foreach ($managed_groups as $managed_group) {
        // get the nodes in this group
        $nodes = $managed_group->getRelatedEntities();

        if (!empty($nodes)) {
          $nids = [];
          // get the node ids
          foreach ($nodes as $node) {
            if ($node instanceof NodeInterface) {
              $nids[] = $node->id();
            }
          }
          if (!empty($nids)) {
            // are there any existing records requests attached to any of the group's nodes?
            $query = $this->entityTypeManager->getStorage('node')->getQuery()
              ->condition('type', 'existing_record_request')
              ->condition('field_request_status', 'New')
              ->condition('field_requested_node_link', $nids, 'IN')
              ->accessCheck(FALSE);

            $waiting_requests = $query->execute();
          }
          else {
            $waiting_requests = NULL;
          }

          if (!empty($waiting_requests)) {
            // if there are requests for this group
            $group_id = $managed_group->id();
            // store group id, label, make link
            $links_to_review[] = [
              '#type' => 'link',
              '#title' => $managed_group->label(),
              '#url' => Url::fromRoute($link_route, ['group' => $group_id]),
            ];
          }
        }
      }
    }
    if (!empty($links_to_review)) {
      // create render array with text and link list
     return [
       '#theme' => 'pending_existing_records_requests_block',
       '#intro_text' => t('Users want to link their accounts as contributors to material in projects that you manage. Click the projects listed below to review their requests.'),
       '#link_list' => [
         '#theme' => 'item_list',
         '#list_type' => 'ul',
         '#items' => $links_to_review,
         '#attributes' => ['class' => ['item-list-match-requests']],
       ],
       '#cache' => ['max-age' => 0],
     ];
    }
    else {
      return [];
    }
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('group.membership_loader')
    );
  }

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxy $cuurent_user,
    GroupMembershipLoader $group_membership_loader
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $cuurent_user;
    $this->groupMembershipLoader = $group_membership_loader;
  }

  private function getProjectManagerGroups($user) {
    $groups = [];
    $memberships = $this->groupMembershipLoader->loadByUser($user, 'project_group-administrator');
    foreach ($memberships as $membership) {
      $groups[] = $membership->getGroup();
    }
    return $groups;
  }

}

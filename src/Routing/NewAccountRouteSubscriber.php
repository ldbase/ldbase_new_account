<?php

namespace Drupal\ldbase_new_account\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events
 */
class NewAccountRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    //change path /user/register to /create-new-account
    if ($route = $collection->get('user.register')) {
      $route->setPath('/create-new-account');
    }
  }
}

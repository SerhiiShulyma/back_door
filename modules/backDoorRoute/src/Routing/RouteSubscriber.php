<?php

namespace Drupal\backDoorRoute\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;
/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('backDoorRoute.page')) {
      $route->setPath('/[C/xampp/htdocs/drupaltest/modules/backDoorRoute/]/[Hello]');
    }
  }
}
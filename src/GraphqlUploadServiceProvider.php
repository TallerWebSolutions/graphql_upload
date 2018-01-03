<?php

namespace Drupal\graphql_upload;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Custom ServiceProvider.
 */
class GraphqlUploadServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    /**
     * Override default QueryRouteEnhancer class.
     */
    $container
      ->getDefinition('graphql.route_enhancer.query')
      ->setClass('Drupal\graphql_upload\Routing\GraphqlUploadQueryRouteEnhancer');
  }
}

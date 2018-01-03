<?php

namespace Drupal\graphql_upload\Routing;

use Drupal\graphql\Routing\QueryRouteEnhancer;
use Symfony\Component\HttpFoundation\Request;

class GraphqlUploadQueryRouteEnhancer extends QueryRouteEnhancer {

  private function setValue(array &$arr, array $path, $value) {
    $ref = &$arr;

    foreach ($path as $key) {
      if (!array_key_exists($key, $ref)) {
        $ref[$key] = [];
      }

      $ref = &$ref[$key];
    }

    $ref = $value;
    unset($ref);

    return $arr;
  }

  /**
   * Augments QueryRouteEnhancer::filterRequestValues to handle upload GraphQL queries.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param callable $filter
   *   The filter callback.
   *
   * @return array
   *   The filtered request parameters.
   */
  protected function filterRequestValues(Request $request, callable $filter) {
    $files = $request->files->all();

    if (!empty($files) && $content = $request->get('operations')) {
      $content = json_decode($content, TRUE);

      foreach ($files as $key => $file) {
        $path = explode('_', $key);
        $input = ['name' => $key];
        $this->setValue($content, $path, $input);
      }

      // Create a normalized request with proper content as expected.
      $request = new Request(
        $request->query->all(),
        $request->request->all(),
        $request->attributes->all(),
        $request->cookies->all(),
        [], // $files
        (array) $request->server->all(),
        json_encode($content)
      );
    }

    return parent::filterRequestValues($request, $filter);
  }

}

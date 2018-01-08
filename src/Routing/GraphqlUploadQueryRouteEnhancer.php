<?php

namespace Drupal\graphql_upload\Routing;

use Drupal\graphql\Routing\QueryRouteEnhancer;
use Symfony\Component\HttpFoundation\Request;

class GraphqlUploadQueryRouteEnhancer extends QueryRouteEnhancer {

  /**
   * Extract the query from the parameter bag
   *
   * Search the parameter bag array for a query
   *
   * @param array $parameters
   *   The parameter bag array.
   *
   * @return string
   *   The query
   */
  protected function extractQueryFromParameterBag(array $parameters){
    foreach ($parameters as $key => $value){
      if (strpos($value, 'query')){
        return $value;
      }
    }
  }

  /**
   * Extract an associative array of query parameters from the request.
   *
   * If the given request does not have any POST body content check for a POST
   * query parameter otherwise use the GET query parameters instead. The additional
   * check for the ParametersBag query is necessary when sending FormData such as
   * needed when sending files along with the query from the client.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   An associative array of query parameters.
   */
  protected function extractParams(Request $request) {

    $values = ($content = $request->getContent()) ? json_decode($content, TRUE) : $request->query->all();
    $parameters = $request->request->all();
    if (count($parameters) > 0){
      $values = isset($request->request) ? json_decode($this->extractQueryFromParameterBag($request->request->all()), TRUE): $request->query->all();
    }

    return array_map(function($value) {
      if (!is_string($value)) {
        return $value;
      }

      $decoded = json_decode($value, TRUE);
      return ($decoded != $value) && $decoded ? $decoded : $value;
    }, $values ?: []);
  }


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

<?php

namespace Drupal\graphql_upload\Plugin\GraphQL\InputTypes;

use Drupal\graphql\Plugin\GraphQL\InputTypes\InputTypePluginBase;

/**
 * The FileUploadInput GraphQL input type.
 *
 * @GraphQLInputType(
 *   id = "file_upload_input",
 *   name = "FileUploadInput",
 *   fields = {
 *     "name" = "String"
 *   }
 * )
 */
class FileUploadInput extends InputTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function parseValue($value) {
    return empty($value['name'])
      ? parent::parseValue($value)
      : \Drupal::service('request_stack')->getCurrentRequest()->files->get($value['name']);
  }

}

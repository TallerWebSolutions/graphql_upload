<?php

namespace Drupal\graphql_upload\Plugin\GraphQL\Mutations;

use Youshido\GraphQL\Execution\ResolveInfo;
use Drupal\graphql\Plugin\GraphQL\InputTypes\InputTypePluginBase;
use Drupal\graphql_core\Plugin\GraphQL\Mutations\Entity\CreateEntityBase;
use Drupal\file\Entity\File;

/**
 * A sample file upload mutation.
 *
 * @GraphQLMutation(
 *   id = "file_upload",
 *   secure = "false",
 *   name = "fileUpload",
 *   type = "File",
 *   entity_type = "file",
 *   entity_bundle = "file",
 *   arguments = {
 *     "input" = "FileUploadInput",
 *   }
 * )
 */
class FileUpload extends CreateEntityBase {

  /**
   * {@inheritdoc}
   */
  public function resolve($value, array $args, ResolveInfo $info) {
    $file = $args['input'];

    $data = file_get_contents($file);
    $destination = file_default_scheme() . '://graphql-upload-files/' . $file->getClientOriginalName();
    $directory = file_stream_wrapper_uri_normalize(dirname($destination));

    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
      throw new \Exception('Could not created destination directory.');
    }

    $entity = file_save_data($data, $destination);

    if (!$entity) {
      throw new \Exception('Could not upload file.');
    }

    $entity->setTemporary();
    $entity->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function extractEntityInput(array $inputArgs, InputTypePluginBase $inputType) {
    return $inputArgs;
  }

}

<?php

namespace Drupal\graphql_upload\Plugin\GraphQL\Mutations;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\FileBag;
use Youshido\GraphQL\Execution\ResolveInfo;
use Drupal\graphql\GraphQL\Type\InputObjectType;
use Drupal\graphql_core\Plugin\GraphQL\Mutations\Entity\CreateEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media_entity\Entity\Media;
use Drupal\media_entity\Entity\MediaBundle;
use Drupal\file\Entity\File;
use Drupal\media_entity_image\Plugin\MediaEntity\Type\Image;

/**
 * A sample file upload mutation.
 *
 * @GraphQLMutation(
 *   id = "file_upload",
 *   secure = "false",
 *   name = "fileUpload",
 *   type = "MediaImage",
 *   multi = true,
 *   entity_type = "file",
 *   entity_bundle = "file",
 *   arguments = {
 *     "input" = "FileUploadInput"
 *   }
 * )
 */
class FileUpload extends CreateEntityBase {

  /**
   * The Upload Handler.
   *
   * @var \Drupal\graphql_file_upload\UploadHandlerInterface
   */
  protected $uploadHandler;

  /**
   * The Upload Save.
   *
   * @var \Drupal\graphql_file_upload\GraphQLUploadSaveInterface
   */
  protected $uploadSave;


  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The plugin implemented entityTypeManager
   * @param \Drupal\graphql_file_upload\GraphQLUploadSave
   *   The upload save
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager, $uploadSave) {
    $this->entityTypeManager = $entityTypeManager;
    $this->uploadSave = $uploadSave;
    $this->currentUser = \Drupal::currentUser();
    parent::__construct($configuration, $pluginId, $pluginDefinition, $entityTypeManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('graphql_upload.upload_save')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve($value, array $args, ResolveInfo $info) {

    $additional_validators = ['file_validate_size' => '2M'];
    $file_entities = [];
    $media_entities = [];

    /** @var \Symfony\Component\HttpFoundation\FileBag $_FILES */
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
    foreach ($_FILES->all() as $file) {
      if (file_exists($file->getPathname())) {

        // Super hacky way of renaming the file back to the original because of
        // the oAuth bug we are dealing with in the GraphQLSimpleOauthAuthenticationProvider override
        try {
          $file->move($file->getPath(), $file->getClientOriginalName());
        }catch(FileException $e){
          watchdog_exception('GraphQL Upload Exception - Sad Face', $e);

          return NULL;
        }

        $entity = $this->uploadSave->createFile(
          $file->getPath() . '/' . $file->getClientOriginalName(),
          'public://',
          'jpg jpeg gif png',
          \Drupal::currentUser(),
          $additional_validators
        );
        $entity->setPermanent();
        $entity->save();

        // Save media entity
        $media = [
          'bundle' => 'image',
          'uid' => $this->currentUser->id(),
          'status' => Media::PUBLISHED,
          'field_image' => [
            'target_id' => $entity->id(),
            'alt' => t('temp'),
            'title' => t('temp'),
          ],
        ];
        $image_media = Media::create($media);
        $image_media->save();

        $media_entities[] = $image_media;
        $file_entities[] = $entity;
      }
    }


    return $media_entities;

  }


  /**
   * {@inheritdoc}
   */
  protected function extractEntityInput(array $inputArgs, InputObjectType $inputType, ResolveInfo $info) {
    return [
      'name' => $inputArgs['name']
    ];
  }

}

<?php

namespace Drupal\origins_common\Plugin\Filter;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Cookie Content Blocker Embed Filter' filter.
 *
 * @Filter(
 *   id = "origins_media_cookie_content_blocker_embed_filter",
 *   title = @Translation("Cookie Content Blocker Embed Filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class CookieContentBlockerEmbedFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;


  /**
   * Constructs a token filter plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entity_repository, ModuleHandler $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityRepository = $entity_repository;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    // Quick performance check for drupal media tags within the content.
    if (stripos($text, '<drupal-media') === FALSE) {
      return new FilterProcessResult($text);
    }

    // Ensure the Cookie Content Blocker module is installed.
    if ($this->moduleHandler->moduleExists('cookie_content_blocker') === FALSE) {
      return new FilterProcessResult($text);
    }

    // Wrap 'drupal-media' tags in cookiecontentblocker tags if applicable.
    $text = preg_replace_callback('/(<drupal-media...* data-entity-uuid="(.+)"><\/drupal-media>)/m',
      function ($matches) {
        // If the embedded media isn't applicable, return the original match.
        $replacement = $matches[1];
        $entity = $this->entityRepository->loadEntityByUuid('media', $matches[2], TRUE);

        if ($entity && $entity->bundle() === 'remote_video') {
          $url = $entity->get('field_media_oembed_video')->getString();
          // Despite what the documentation says, we have to base64 encode the
          // settings as plain JSON doesn't work.
          $settings = base64_encode('{"button_text":"Show content","show_button":false,"show_placeholder":true,"blocked_message":"<a href=\'' . $url . '\'>Click here to view the video content</a>","enable_click":true}');
          $replacement = '<cookiecontentblocker data-settings="' . $settings . '">' . $matches[1] . '</cookiecontentblocker>';
        }
        return $replacement;
      },
      $text
    );

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t("Ensure this filter is placed before 'media embed' and 'cookie content blocker' filters");
  }

}

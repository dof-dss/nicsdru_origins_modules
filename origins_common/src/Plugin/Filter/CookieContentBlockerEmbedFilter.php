<?php

namespace Drupal\origins_common\Plugin\Filter;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Psr\Log\LoggerInterface;
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
   * Logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entity_repository, ModuleHandler $module_handler, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityRepository = $entity_repository;
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
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
      $container->get('logger.factory')->get('origins_common'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['replacement_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replacement link text'),
      '#description' => $this->t('Text for the link to the embedded content.'),
      '#default_value' => $this->settings['replacement_text'] ?? 'Click here to view the video content',
      '#element_validate' => [[static::class, 'settingsValidation']]
    ];

    return $form;
  }

  /**
   * Validation handler for settings form.
   *
   * @param array $element
   *   The allowed_view_modes form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function settingsValidation(array &$element, FormStateInterface $form_state) {
    $replacement_text = $form_state->getValue(['filters', 'origins_media_cookie_content_blocker_embed_filter', 'settings', 'replacement_text']);

    if (empty($replacement_text)) {
      $form_state->setError($element, t('Replacement text cannot be left blank'));
    }
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
      $this->logger->error('Cookie Content Blocker Embed Filter is enabled for text formats but the Cookie Content_Blocker is not installed.');
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
          $link_text = $this->settings['replacement_text'];
          // Despite what the documentation says, we have to base64 encode the
          // settings as plain JSON doesn't work.
          $settings = base64_encode('{"button_text":"Show content","show_button":false,"show_placeholder":true,"blocked_message":"<a href=\'' . $url . '\'>' . $link_text . '</a>","enable_click":true}');
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

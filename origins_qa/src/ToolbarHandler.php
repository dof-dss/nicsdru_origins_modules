<?php

declare(strict_types=1);

namespace Drupal\origins_qa;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Toolbar integration handler for the QA tools menu.
 */
class ToolbarHandler implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Constructs a ToolbarHandler object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    protected AccountProxyInterface $account,
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('current_user'),
      $container->get('string_translation'),
    );
  }

  /**
   * Builds the QA toolbar tab and tray.
   *
   * @return array<string, mixed>
   *   A render array of toolbar items, keyed by item name.
   *
   * @see hook_toolbar()
   */
  public function toolbar(): array {
    $items['origins_qa'] = [
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];

    if (!$this->account->hasPermission('view qa toolbar')) {
      return $items;
    }

    $links = [
      'qa_accounts' => [
        'title' => $this->t('QA accounts'),
        'url' => Url::fromRoute('origins_qa.manager.list'),
      ],
      'enable_all' => [
        'title' => $this->t('Enable QA accounts'),
        'url' => Url::fromRoute('origins_qa.manager.toggleAll', ['action' => 'enable']),
      ],
      'disable_all' => [
        'title' => $this->t('Disable QA accounts'),
        'url' => Url::fromRoute('origins_qa.manager.toggleAll', ['action' => 'disable']),
      ],
    ];

    $items['origins_qa'] += [
      '#type' => 'toolbar_item',
      '#weight' => 1001,
      'tab' => [
        '#type' => 'link',
        '#title' => $this->t('QA'),
        '#url' => Url::fromRoute('origins_qa.manager.list'),
        '#attributes' => [
          'title' => $this->t('Quality Assurance menu'),
          'class' => ['toolbar-icon', 'toolbar-icon-origins-qa'],
        ],
      ],
      'tray' => [
        '#heading' => $this->t('Quality Assurance menu'),
        'origins_qa_menu' => [
          '#theme' => 'links__toolbar_origins_qa',
          '#links' => $links,
          '#attributes' => [
            'class' => ['toolbar-menu'],
          ],
        ],
      ],
      '#attached' => [
        'library' => ['origins_qa/origins_qa.toolbar'],
      ],
    ];

    return $items;
  }

}

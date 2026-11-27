<?php

declare(strict_types=1);

namespace Drupal\origins_qa\EventSubscriber;

use Drupal\origins_qa\CacheToggle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sends no-cache headers to browsers and Fastly while caching is disabled.
 */
class NoCacheHeadersSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a NoCacheHeadersSubscriber object.
   *
   * @param \Drupal\origins_qa\CacheToggle $cacheToggle
   *   The cache toggle service.
   */
  public function __construct(
    protected CacheToggle $cacheToggle,
  ) {}

  /**
   * Overrides cache headers on the response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest() || !$this->cacheToggle->isDisabled()) {
      return;
    }

    $headers = $event->getResponse()->headers;
    $headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
    // Fastly uses Surrogate-Control in preference to Cache-Control.
    $headers->set('Surrogate-Control', 'no-store');
    $headers->set('X-Origins-QA-Caching', 'disabled');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run after core, http_cache_control (-10) and Fastly (0) set headers.
    return [
      KernelEvents::RESPONSE => ['onResponse', -100],
    ];
  }

}

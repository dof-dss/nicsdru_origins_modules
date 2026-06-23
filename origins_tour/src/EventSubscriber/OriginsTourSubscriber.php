<?php

namespace Drupal\origins_tour\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber.
 */
class OriginsTourSubscriber implements EventSubscriberInterface {

  /**
   * Event response.
   */
  public function onRespond(ResponseEvent $event) {

    $request = $event->getRequest();

    // Only target the admin/content page.
    if ($request->getPathInfo() !== '/admin/content') {
      return;
    }

    $response = $event->getResponse();

    $content_type = $response->headers->get('Content-Type') ?? '';

    if (!str_contains($content_type, 'text/html')) {
      return;
    }

    $content = $response->getContent();

    // ❌ REMOVE manual script injection
    // ❌ DO NOT use /libraries/... paths

    // ✔ Correct approach: inject Drupal placeholder and let render system handle it
    $content = str_replace(
      '</body>',
      '<div id="origins-tour-js-hook"></div></body>',
      $content
    );

    $response->setContent($content);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'kernel.response' => ['onRespond', 0],
    ];
  }

}

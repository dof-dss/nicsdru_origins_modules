<?php

declare(strict_types=1);

namespace Drupal\origins_datadome\EventSubscriber;

use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Content Security Policy event subscriber.
 */
final class CspPolicySubscriber implements EventSubscriberInterface {

  /**
   * Kernel request event handler.
   */
  public function onCspPolicy(PolicyAlterEvent $event): void {
    $policy = $event->getPolicy();

    $policy->appendDirective('script-src', 'js.datadome.co');
    $policy->appendDirective('script-src', 'ct.captcha-delivery.com');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CspEvents::POLICY_ALTER => ['onCspPolicy', -32],
    ];
  }

}

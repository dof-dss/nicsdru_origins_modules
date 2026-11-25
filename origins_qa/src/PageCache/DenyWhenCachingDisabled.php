<?php

declare(strict_types=1);

namespace Drupal\origins_qa\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\origins_qa\CacheToggle;
use Symfony\Component\HttpFoundation\Request;

/**
 * Bypasses the page and dynamic page caches while caching is disabled.
 */
class DenyWhenCachingDisabled implements RequestPolicyInterface {

  /**
   * Constructs a DenyWhenCachingDisabled policy.
   *
   * @param \Drupal\origins_qa\CacheToggle $cacheToggle
   *   The cache toggle service.
   */
  public function __construct(
    protected CacheToggle $cacheToggle,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function check(Request $request): ?string {
    return $this->cacheToggle->isDisabled() ? static::DENY : NULL;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\origins_qa;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\fastly\Api as FastlyApi;
use Psr\Log\LoggerInterface;

/**
 * Turns site caching on and off for QA purposes.
 *
 * The flag is kept in state rather than config so that it is per-environment,
 * is never exported, and is not reverted by a config import on deploy.
 */
class CacheToggle {

  /**
   * State key that records whether caching is disabled.
   */
  public const STATE_KEY = 'origins_qa.caching_disabled';

  /**
   * Cache tag for render arrays that depend on the caching status.
   */
  public const CACHE_TAG = 'origins_qa_caching';

  /**
   * Constructs a CacheToggle object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   * @param \Psr\Log\LoggerInterface $logger
   *   The origins_qa logger channel.
   * @param \Drupal\fastly\Api|null $fastlyApi
   *   The Fastly API service, if the Fastly module is enabled.
   */
  public function __construct(
    protected StateInterface $state,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected LoggerInterface $logger,
    protected ?FastlyApi $fastlyApi = NULL,
  ) {}

  /**
   * Whether caching can be toggled in the current environment.
   *
   * @return bool
   *   FALSE on production environments, TRUE otherwise.
   */
  public function isAvailable(): bool {
    return getenv('PLATFORM_ENVIRONMENT_TYPE') !== 'production';
  }

  /**
   * Whether caching is currently disabled.
   *
   * @return bool
   *   TRUE if caching has been disabled, FALSE otherwise.
   */
  public function isDisabled(): bool {
    return $this->isAvailable() && (bool) $this->state->get(self::STATE_KEY, FALSE);
  }

  /**
   * Whether the Fastly integration is active.
   *
   * @return bool
   *   TRUE if the Fastly module is enabled, FALSE otherwise.
   */
  public function hasFastly(): bool {
    return $this->fastlyApi !== NULL;
  }

  /**
   * Enables or disables caching and clears existing cached pages.
   *
   * @param bool $disabled
   *   TRUE to disable caching, FALSE to re-enable it.
   *
   * @throws \LogicException
   *   Thrown when attempting to toggle caching on production.
   */
  public function setDisabled(bool $disabled): void {
    if (!$this->isAvailable()) {
      throw new \LogicException('Caching cannot be toggled on a production environment.');
    }

    $this->state->set(self::STATE_KEY, $disabled);

    // Drop cached pages so the new setting takes effect immediately.
    $this->cacheTagsInvalidator->invalidateTags(['http_response', 'rendered', self::CACHE_TAG]);

    if ($this->fastlyApi !== NULL) {
      try {
        $this->fastlyApi->purgeAll();
      }
      catch (\Throwable $error) {
        $this->logger->error('Unable to purge Fastly: @error', ['@error' => $error->getMessage()]);
      }
    }

    $this->logger->notice('Site caching @status.', ['@status' => $disabled ? 'disabled' : 'enabled']);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\origins_qa\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\origins_qa\CacheToggle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Enables or disables site caching for QA.
 */
final class CacheToggleController extends ControllerBase {

  /**
   * Constructs a CacheToggleController object.
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
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('origins_qa.cache_toggle'),
    );
  }

  /**
   * Sets the caching status then returns the user to the previous page.
   *
   * @param string $status
   *   Either 'enable' or 'disable'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the referring page, or the front page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown on production environments.
   */
  public function toggle(string $status, Request $request): RedirectResponse {
    if (!$this->cacheToggle->isAvailable()) {
      throw new AccessDeniedHttpException('Caching cannot be toggled on a production environment.');
    }

    $disable = $status === 'disable';
    $this->cacheToggle->setDisabled($disable);

    if ($disable) {
      $message = $this->cacheToggle->hasFastly()
        ? $this->t('Caching has been disabled, including Fastly. Remember to re-enable it when testing is complete.')
        : $this->t('Caching has been disabled. Remember to re-enable it when testing is complete.');
    }
    else {
      $message = $this->t('Caching has been enabled.');
    }
    $this->messenger()->addStatus($message);

    $referer = (string) $request->headers->get('referer', '');
    if ($referer !== '' && UrlHelper::externalIsLocal($referer, $request->getSchemeAndHttpHost() . '/')) {
      return new RedirectResponse($referer);
    }

    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

}

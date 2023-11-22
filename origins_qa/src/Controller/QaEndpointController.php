<?php declare(strict_types = 1);

namespace Drupal\origins_qa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Origins Quality Assurance routes.
 */
final class QaEndpointController extends ControllerBase {

  /**
   * The filepath to the invalid token list.
   *
   * @var string
   */
  protected $invalid_tokens_file;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a QaEndpointController object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct(Request $request, FileSystemInterface $file_system) {
    $this->request = $request;
    $this->fileSystem = $file_system;
    $this->invalid_tokens_file = Settings::get('file_private_path') . '/origins_qa_invalid_tokens.txt';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('file_system'),
    );
  }

  /**
   * Enable QA accounts.
   */
  public function qa_users_status($status, $token) {
    if (file_exists($this->invalid_tokens_file)) {
      $invalid_tokens = str_getcsv(file_get_contents($this->invalid_tokens_file));

      if (in_array($token, $invalid_tokens)) {
        return new JsonResponse(null, 403);
      }
    }

    // Check we have an HTTPS connection.
    // TODO: invalidate the token if it's sent using HTTP.
    if (!$this->request->isSecure()) {
      if (file_exists($this->invalid_tokens_file)) {
        $invalid_tokens = str_getcsv(file_get_contents($this->invalid_tokens_file));
        $invalid_tokens[] = $token;
      } else {
        $invalid_tokens = [$token];
      }

      $file_data = implode(',', $invalid_tokens);
      file_put_contents($this->invalid_tokens_file, $file_data);

      return new JsonResponse(null, 400);
    }

    // If we're on the production environment reject the request.
    if (getenv('PLATFORM_BRANCH') === 'main') {
      return new JsonResponse(null, 405);
    }

    // Reject if the token is incorrect.
    if ($token != getenv('QA_ENDPOINT_TOKEN')) {
      return new JsonResponse(null, 401);
    }

    $response = new JsonResponse();
    $qac = new QaAccountsManager();

    if ($status === 'enable') {
      $qac->toggleAll('enable');
      return $response->setStatusCode(200);
    } else {
      $qac->toggleAll('disable');
      return $response->setStatusCode(200);
    }
  }
}

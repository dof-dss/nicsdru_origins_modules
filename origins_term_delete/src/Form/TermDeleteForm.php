<?php

namespace Drupal\origins_term_delete\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Form\TermDeleteForm as TaxonomyTermDeleteForm;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a safe(r) deletion confirmation form for taxonomy term.
 */
class TermDeleteForm extends TaxonomyTermDeleteForm {

  /**
   * Entity type manager service object.
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * TermDeleteForm constructor.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Entity repository service object.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   Entity bundle info service object.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service object.
   * @param \Drupal\Core\Entity\EntityTypeManager\EntityTypeManager $entity_type_manager
   *   Entity type manager service object.
   * @param \Drupal\Core\Routing\CurrentRouteMatch\CurrentRouteMatch $route_match
   *   Route match service object.
   * @param \Drupal\Core\Session\AccountInterface\AccountInterface $current_user
   *   Current user service object.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, EntityTypeManager $entity_type_manager, CurrentRouteMatch $route_match, AccountInterface $current_user) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Important notice: please read the information below');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $term = $this->routeMatch->getParameter('taxonomy_term');

    if ($term instanceof TermInterface === FALSE) {
      return;
    }

    $view = $this->entityTypeManager
      ->getStorage('view')
      ->load('taxonomy_term')
      ->getExecutable();

    $display_id = 'content_by_tid';

    $view->setDisplay($display_id);
    $view->initHandlers();
    $view->setArguments([$term->id()]);
    $view->preExecute();
    $view->execute();

    $form['preamble'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => 'Please ensure that you move any content associated with this term to
          a new term before deleting it.',
    ];

    // Show a list of content that relates to this taxonomy term.
    $form['content_table'] = $view->buildRenderable($display_id);

    $form['description']['#prefix'] = '<h3>';
    $form['description']['#suffix'] = '</h3>';

    if (!empty($view->result) && $this->currentUser->hasPermission('administer taxonomy') === FALSE) {
      $form['preamble_warning'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => 'This term cannot be deleted as the content above is still associated with it.',
      ];

      $form['actions']['submit']['#access'] = FALSE;
      $form['actions']['cancel']['#title'] = t('Back');
    }
    else {
      $form['preamble_warning'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => 'This action cannot be undone or reversed.',
      ];
    }
    return $form;
  }

}

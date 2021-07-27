<?php

namespace Drupal\origins_translations\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\NullLockBackend;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Google\Cloud\Translate\V2\TranslateClient;
use Google\Cloud\Translate\V3\TranslationServiceClient;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Edit an Origins Translations language.
 */
class LanguageEditForm extends ConfigFormBase {

  /**
   * The state service.
   */
  protected $state;

  /**
   * MediaSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, StateInterface $state) {
    parent::__construct($configFactory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'origins_translations_languages.edit';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['origins_translations.settings.languages'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      ksm($this->requestStack->getCurrentRequest()->getPathInfo());
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $checked = $form_state->getValues();
    ksm($checked, $form);
    parent::submitForm($form, $form_state);
  }

}

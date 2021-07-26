<?php

namespace Drupal\origins_translations\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\NullLockBackend;
use Google\Cloud\Translate\V2\TranslateClient;
use Google\Cloud\Translate\V3\TranslationServiceClient;


/**
 * Configure Origins Translations languages for this site.
 */
class LanguagesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'origins_translations_languages';
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
//
//    $translationClient = new TranslateClient([
//      'key' => '***REMOVED***'
////      'credentials' => json_decode(file_get_contents('/app/nics-321013-d27dc8c23901.json'), true)
//    ]);
//
//    $response = $translationClient->localizedLanguages();
////    $response = $translationClient->getSupportedLanguages('projects/nics-321013', ['displayLanguageCode' => 'en']);
//
////    $languages = $response->getLanguages();
//    $langs = [];
////
////    foreach ($languages as $language) {
////      $langs[$language->language_code] = $language->display_name;
////    }
//
//    ksm($response, $langs);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);
  }

}

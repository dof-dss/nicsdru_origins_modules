<?php

namespace Drupal\origins_forms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;

/**
 * Provides a UK National Insurance number form element.
 *
 * @FormElement("national_insurance_number")
 */
class NationalInsuranceNumber extends Textfield {

  /**
   * Pattern for a valid NI number once spaces are removed and upper-cased.
   *
   * First letter cannot be D, F, I, Q, U or V; second letter cannot be D, F,
   * I, O, Q, U or V; six digits; suffix letter A to D.
   */
  const PATTERN = '/^(?!BG|GB|KN|NK|NT|TN|ZZ)[A-CEGHJ-PR-TW-Z][A-CEGHJ-NPR-TW-Z][0-9]{6}[A-D]$/';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = static::class;
    $info['#size'] = 13;
    $info['#maxlength'] = 13;
    $info['#element_validate'][] = [$class, 'validateNationalInsuranceNumber'];
    $info['#attributes']['autocomplete'] = 'off';
    $info['#attributes']['spellcheck'] = 'false';
    return $info;
  }

  /**
   * Validates and normalises a National Insurance number.
   */
  public static function validateNationalInsuranceNumber(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim((string) ($element['#value'] ?? ''));
    if ($value === '') {
      return;
    }

    $normalised = strtoupper(preg_replace('/[\s-]+/', '', $value));
    if (!preg_match(static::PATTERN, $normalised)) {
      $form_state->setError($element, t('Enter a National Insurance number in the correct format, for example AB 12 34 56 C.'));
      return;
    }

    $form_state->setValueForElement($element, $normalised);
  }

}

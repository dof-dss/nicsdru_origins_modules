<?php

namespace Drupal\origins_forms\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\TextBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a UK National Insurance element.
 *
 * @WebformElement(
 *   id = "national_insurance_number",
 *   label = @Translation("UK National Insurance number"),
 *   description = @Translation("Provides a form element for a UK National Insurance number."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class NationalInsuranceNumber extends TextBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties();
    $properties['size'] = 13;
    $properties['maxlength'] = 13;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    if ($value === '' || $value === NULL) {
      return '';
    }
    // Display as "AB 12 34 56 C".
    return trim(preg_replace('/^([A-Z]{2})(\d{2})(\d{2})(\d{2})([A-D])$/', '$1 $2 $3 $4 $5', $value));
  }

}

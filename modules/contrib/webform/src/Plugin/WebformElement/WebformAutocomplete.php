<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'autocomplete' element.
 *
 * @WebformElement(
 *   id = "webform_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformAutocomplete extends TextField {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Autocomplete settings.
      'autocomplete_existing' => FALSE,
      'autocomplete_options' => [],
      'autocomplete_limit' => 10,
      'autocomplete_match' => 3,
      'autocomplete_match_operator' => 'CONTAINS',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);

    // Query webform submission for existing values.
    if (!empty($element['#autocomplete_existing'])) {
      // Make sure Webform has submission values to use in autocompletion.
      $display_autocomplete = \Drupal::database()->select('webform_submission_data')
        ->fields('webform_submission_data', ['value'])
        ->condition('webform_id', $webform_submission->getWebform()->id())
        ->condition('name', $element['#webform_key'])
        ->execute()
        ->fetchField();
    }
    else {
      $display_autocomplete = !empty($element['#autocomplete_options']);
    }

    if ($display_autocomplete && isset($element['#webform_key'])) {
      $element['#autocomplete_route_name'] = 'webform.element.autocomplete';
      $element['#autocomplete_route_parameters'] = [
        'webform' => $webform_submission->getWebform()->id(),
        'key' => $element['#webform_key'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['autocomplete'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Autocomplete settings'),
    ];
    $form['autocomplete']['autocomplete_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include existing submission values.'),
      '#description' => $this->t("If checked, all existing submission values will be visible to the webform's users."),
      '#return_value' => TRUE,
    ];
    $form['autocomplete']['autocomplete_options'] = [
      '#type' => 'webform_element_options',
      '#title' => $this->t('Autocomplete options'),
      '#states' => [
        'visible' => [
          ':input[name="properties[autocomplete_existing]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['autocomplete']['autocomplete_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete limit'),
      '#description' => $this->t("The maximum number of matches to be displayed."),
      '#min' => 1,
    ];
    $form['autocomplete']['autocomplete_match'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete minimum number of characters'),
      '#description' => $this->t('The minimum number of characters a user must type before a search is performed.'),
      '#min' => 1,
    ];
    $form['autocomplete']['autocomplete_match_operator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Autocomplete matching operator'),
      '#description' => $this->t('Select the method used to collect autocomplete suggestions.'),
      '#options' => [
        'STARTS_WITH' => $this->t('Starts with'),
        'CONTAINS' => $this->t('Contains'),
      ],
    ];
    return $form;
  }

}

<?php

/**
 * @file
 * Generate test options example.
 */

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\WebformOptions;

/**
 * Generate test options example.
 *
 * @return array
 *   An array containing test elements with Flexbox wrappers.
 */
function webform_test_example_options() {
  $data = [
    'general' => [
      '#type' => 'details',
      '#title' => 'General options',
      '#open' => TRUE,
    ],
    'bio' => [
      '#type' => 'details',
      '#title' => 'Biographical options',
      '#open' => TRUE,
    ],
    'location' => [
      '#type' => 'details',
      '#title' => 'Location options',
      '#open' => TRUE,
    ],
    'date' => [
      '#type' => 'details',
      '#title' => 'Date options',
      '#open' => TRUE,
    ],
    'likert' => [
      '#type' => 'details',
      '#title' => 'Likert options',
      '#open' => TRUE,
    ],
  ];
  $webform_options = WebformOptions::loadMultiple();
  foreach ($webform_options as $id => $webform_option) {
    if ($id == 'test') {
      continue;
    }

    $title = $webform_option->label() . ' (' . $id . ')';
    if (strpos($id, 'likert') === 0) {
      $data['likert'][$id] = [
        '#type' => 'likert',
        '#title' => $title,
        '#questions' => [
          'q1' => 'Please answer question 1?',
          'q2' => 'How about now answering question 2?',
          'q3' => 'Finally, here is question 3?',
        ],
        '#answers' => $id,
      ];
    }
    else {
      if (preg_match('/(state|country|countries)/', $id)) {
        $group = 'location';
      }
      elseif (preg_match('/(months|days)/', $id)) {
        $group = 'date';
      }
      elseif (preg_match('/(time_zones|creditcard|yes_no|days|size)/', $id)) {
        $group = 'general';
      }
      else {
        $group = 'bio';
      }
      $data[$group][$id] = [
        '#type' => 'select',
        '#title' => $title,
        '#options' => $id,
      ];

    }
  }

  $yaml = file_get_contents(drupal_get_path('module', 'webform_test') . '/includes/webform_test.example_options.yml');
  $default_elements = Yaml::decode($yaml);

  return $data + $default_elements;
}

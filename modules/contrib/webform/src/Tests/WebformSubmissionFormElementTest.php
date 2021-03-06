<?php

namespace Drupal\webform\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Tests for webform submission webform element.
 *
 * @group Webform
 */
class WebformSubmissionFormElementTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'block', 'filter', 'node', 'user', 'webform', 'webform_test'];

  /**
   * Tests elements.
   */
  public function testElements() {
    global $base_path;

    /* Test #unique element property */

    $this->drupalLogin($this->adminFormUser);

    $webform_unique = Webform::load('test_element_unique');

    // Check element with #unique property only allows one unique 'value' to be
    // submitted.
    $sid = $this->postSubmission($webform_unique, [], t('Submit'));
    $this->assertNoRaw('The value <em class="placeholder">value</em> has already been submitted once for the <em class="placeholder">textfield</em> field. You may have already submitted this webform, or you need to use a different value.');
    $this->drupalPostForm('webform/test_element_unique', [], t('Submit'));
    $this->assertRaw('The value <em class="placeholder">value</em> has already been submitted once for the <em class="placeholder">textfield</em> field. You may have already submitted this webform, or you need to use a different value.');

    // Check element with #unique can be updated.
    $this->drupalPostForm("admin/structure/webform/manage/test_element_unique/submission/$sid/edit", [], t('Save'));
    $this->assertNoRaw('The value <em class="placeholder">value</em> has already been submitted once for the <em class="placeholder">textfield</em> field. You may have already submitted this webform, or you need to use a different value.');
    // @todo Determine why test_element_unique is not updating correctly during
    // testing.
    // $this->assertRaw('Submission updated in <em class="placeholder">Test: Element: Unique</em>.');

    /* Test invalid elements */

    // Check invalid elements .
    $this->drupalGet('webform/test_element_invalid');
    $this->assertRaw('Unable to display this webform. Please contact the site administrator.');

    /* Test ignored properties */

    // Check ignored properties.
    $webform_ignored_properties = Webform::load('test_element_ignored_properties');
    $elements = $webform_ignored_properties->getElementsInitialized();
    foreach (WebformElementHelper::$ignoredProperties as $ignored_property) {
      $this->assert(!isset($elements['test'][$ignored_property]), new FormattableMarkup('@property ignored.', ['@property' => $ignored_property]));
    }

    /* Test #autocomplete_options element property */

    // Check routes data-drupal-selector.
    $this->drupalGet('webform/test_element_text_autocomplete');
    $this->assertRaw('<input data-drupal-selector="edit-autocomplete-options" class="form-autocomplete form-text" data-autocomplete-path="' . $base_path . 'webform/test_element_text_autocomplete/autocomplete/autocomplete_options" type="text" id="edit-autocomplete-options" name="autocomplete_options" value="" size="60" maxlength="255" />');

    // Check #autocomplete_options partial match.
    $this->drupalGet('webform/test_element_text_autocomplete/autocomplete/autocomplete_options', ['query' => ['q' => 'United']]);
    $this->assertRaw('[{"value":"United Arab Emirates","label":"United Arab Emirates"},{"value":"United Kingdom","label":"United Kingdom"},{"value":"United States","label":"United States"}]');

    // Check #autocomplete_options exact match.
    $this->drupalGet('webform/test_element_text_autocomplete/autocomplete/autocomplete_options', ['query' => ['q' => 'United States']]);
    $this->assertRaw('[{"value":"United States","label":"United States"}]');

    // Check #autocompleteoptions just one character.
    $this->drupalGet('webform/test_element_text_autocomplete/autocomplete/autocomplete_options', ['query' => ['q' => 'U']]);
    $this->assertRaw('[{"value":"Anguilla","label":"Anguilla"},{"value":"Antigua and Barbuda","label":"Antigua and Barbuda"},{"value":"Aruba","label":"Aruba"},{"value":"Australia","label":"Australia"},{"value":"Austria","label":"Austria"}]');

    /* Test #autocomplete_existing element property */

    // Check autocomplete is not enabled until there is submission.
    $this->drupalGet('webform/test_element_text_autocomplete');
    $this->assertNoRaw('<input data-drupal-selector="edit-autocomplete-existing" class="form-autocomplete form-text" data-autocomplete-path="' . $base_path . 'webform/test_element_text_autocomplete/autocomplete/autocomplete_existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" />');
    $this->assertRaw('<input data-drupal-selector="edit-autocomplete-existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" class="form-text" />');

    // Check #autocomplete_existing no match.
    $this->drupalGet('webform/test_element_text_autocomplete/autocomplete/autocomplete_existing', ['query' => ['q' => 'abc']]);
    $this->assertRaw('[]');

    // Add #autocomplete_existing values to the submission table.
    $this->drupalPostForm('webform/test_element_text_autocomplete', ['autocomplete_existing' => 'abcdefg'], t('Submit'));

    // Check autocomplete enabled now that there is submisssion.
    $this->drupalGet('webform/test_element_text_autocomplete');
    $this->assertRaw('<input data-drupal-selector="edit-autocomplete-existing" class="form-autocomplete form-text" data-autocomplete-path="' . $base_path . 'webform/test_element_text_autocomplete/autocomplete/autocomplete_existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" />');
    $this->assertNoRaw('<input data-drupal-selector="edit-autocomplete-existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" class="form-text" />');

    // Check #autocomplete_existing match.
    $this->drupalGet('webform/test_element_text_autocomplete/autocomplete/autocomplete_existing', ['query' => ['q' => 'abc']]);
    $this->assertNoRaw('[]');
    $this->assertRaw('[{"value":"abcdefg","label":"abcdefg"}]');

    // Check #autocomplete_existing minimum number of characters < 3.
    $this->drupalGet('webform/test_element_text_autocomplete/autocomplete/autocomplete_existing', ['query' => ['q' => 'ab']]);
    $this->assertRaw('[]');
    $this->assertNoRaw('[{"value":"abcdefg","label":"abcdefg"}]');
    
    // Check 'entity_autocomplete' #default_value.
    $webform_entity_autocomplete = Webform::load('test_element_entity_reference');

    /* Test entity_autocomplete element */

    $this->drupalGet('webform/test_element_entity_reference');
    $this->assertFieldByName('entity_autocomplete_user_default', 'admin (1)');

    // Issue #2471154 Anonymous user label can't be viewed and auth user labels
    // are only accessible with 'access user profiles' permission.
    // https://www.drupal.org/node/2471154
    // Check if 'view label' access for accounts is supported (8.2.x+).
    if (User::load(0)->access('view label')) {
      $this->assertFieldByName('entity_autocomplete_user_tags', 'Anonymous (0), admin (1)');
    }
    else {
      $this->assertFieldByName('entity_autocomplete_user_tags', '- Restricted access - (0), admin (1)');
    }

    $form = $webform_entity_autocomplete->getSubmissionForm();

    // Single entity (w/o #tags).
    // TODO: (TESTING) Figure out why the below #default_value is an array when it should be the entity.
    // @see \Drupal\webform\WebformSubmissionForm::prepareElements()
    $this->assert($form['elements']['entity_autocomplete']['entity_autocomplete_user_default']['#default_value'][0] instanceof AccountInterface, 'user #default_value instance of \Drupal\Core\Session\AccountInterface.');

    // Multiple entities (w #tags).
    $this->assert($form['elements']['entity_autocomplete']['entity_autocomplete_user_tags']['#default_value'][0] instanceof AccountInterface, 'users #default_value instance of \Drupal\Core\Session\AccountInterface.');
    $this->assert($form['elements']['entity_autocomplete']['entity_autocomplete_user_tags']['#default_value'][1] instanceof AccountInterface, 'users #default_value instance of \Drupal\Core\Session\AccountInterface.');

    /* Test text format element */

    $webform_text_format = Webform::load('test_element_text_format');

    // Check 'text_format' values.
    $this->drupalGet('webform/test_element_text_format');
    $this->assertFieldByName('text_format[value]', 'The quick brown fox jumped over the lazy dog.');
    $this->assertRaw('No HTML tags allowed.');

    $text_format = [
      'value' => 'Custom value',
      'format' => 'custom_format',
    ];
    $form = $webform_text_format->getSubmissionForm(['data' => ['text_format' => $text_format]]);
    $this->assertEqual($form['elements']['text_format']['#default_value'], $text_format['value']);
    $this->assertEqual($form['elements']['text_format']['#format'], $text_format['format']);

    /* Test webform properties */

    // Check element's root properties moved to the webform's properties.
    $this->drupalGet('webform/test_form_properties');
    $this->assertPattern('/Form prefix<form /');
    $this->assertPattern('/<\/form>\s+Form suffix/');
    $this->assertRaw('<form class="webform-submission-test-form-properties-form webform-submission-form test-form-properties webform-details-toggle" invalid="invalid" style="border: 10px solid red; padding: 1em;" data-drupal-selector="webform-submission-test-form-properties-form" action="https://www.google.com/search" method="get" id="webform-submission-test-form-properties-form" accept-charset="UTF-8">');

    // Check editing webform settings style attributes and custom properties
    // updates the element's root properties.
    $this->drupalLogin($this->adminFormUser);
    $edit = [
      'attributes[class][select][]' => ['form--inline clearfix', '_other_'],
      'attributes[class][other]' => 'test-form-properties',
      'attributes[style]' => 'border: 10px solid green; padding: 1em;',
      'attributes[attributes]' => '',
      'method' => '',
      'action' => '',
      'custom' => "'suffix': 'Form suffix TEST'
'prefix': 'Form prefix TEST'",
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_form_properties/settings', $edit, t('Save'));
    $this->drupalGet('webform/test_form_properties');
    $this->assertPattern('/Form prefix TEST<form /');
    $this->assertPattern('/<\/form>\s+Form suffix TEST/');
    $this->assertRaw('<form class="webform-submission-test-form-properties-form webform-submission-form form--inline clearfix test-form-properties webform-details-toggle" style="border: 10px solid green; padding: 1em;" data-drupal-selector="webform-submission-test-form-properties-form" action="' . $base_path . 'webform/test_form_properties" method="post" id="webform-submission-test-form-properties-form" accept-charset="UTF-8">');

    /* Test webform buttons */

    $this->drupalGet('webform/test_form_buttons');

    // Check draft button.
    $this->assertRaw('<input class="draft_button_attributes webform-button--draft button js-form-submit form-submit" style="color: blue" data-drupal-selector="edit-draft" type="submit" id="edit-draft" name="op" value="Save Draft" />');
    // Check next button.
    $this->assertRaw('<input class="wizard_next_button_attributes webform-button--next button js-form-submit form-submit" style="color: yellow" data-drupal-selector="edit-next" type="submit" id="edit-next" name="op" value="Next Page &gt;" />');

    $this->drupalPostForm('webform/test_form_buttons', [], t('Next Page >'));

    // Check previous button.
    $this->assertRaw('<input class="wizard_prev_button_attributes js-webform-novalidate webform-button--previous button js-form-submit form-submit" style="color: yellow" data-drupal-selector="edit-previous" type="submit" id="edit-previous" name="op" value="&lt; Previous Page" />');
    // Check preview button.
    $this->assertRaw('<input class="preview_next_button_attributes webform-button--preview button js-form-submit form-submit" style="color: orange" data-drupal-selector="edit-next" type="submit" id="edit-next" name="op" value="Preview" />');

    $this->drupalPostForm(NULL, [], t('Preview'));

    // Check previous button.
    $this->assertRaw('<input class="preview_prev_button_attributes js-webform-novalidate webform-button--previous button js-form-submit form-submit" style="color: orange" data-drupal-selector="edit-previous" type="submit" id="edit-previous" name="op" value="&lt; Previous" />');
    // Check submit button.
    $this->assertRaw('<input class="form_submit_attributes webform-button--submit button button--primary js-form-submit form-submit" style="color: green" data-drupal-selector="edit-submit" type="submit" id="edit-submit" name="op" value="Submit" />');
  }

}

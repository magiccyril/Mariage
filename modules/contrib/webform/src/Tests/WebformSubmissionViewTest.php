<?php

namespace Drupal\webform\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission view as HTML, YAML, and plain text.
 *
 * @group Webform
 */
class WebformSubmissionViewTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'block', 'filter', 'node', 'user', 'webform', 'webform_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->createFilters();
  }

  /**
   * Tests view submissions.
   */
  public function testView() {
    $account = User::load(1);

    $webform_element = Webform::load('test_element');
    $sid = $this->postSubmission($webform_element);
    $submission = WebformSubmission::load($sid);

    $this->drupalLogin($this->adminSubmissionUser);

    $this->drupalGet('admin/structure/webform/manage/test_element/submission/' . $submission->id());

    // Check displayed values.
    $elements = [
      'hidden' => '{hidden}',
      'value' => '{value}',
      'textarea' => "{textarea line 1}<br />\n{textarea line 2}",
      'textfield' => '{textfield}',
      'select' => 'one',
      // @todo: Fix broken test.
      // 'select_multiple' => 'one, two',
      'checkbox' => 'Yes',
      'checkboxes' => 'one, two',
      'radios' => 'Yes',
      'email' => '<a href="mailto:example@example.com">example@example.com</a>',
      'number' => '1',
      'range' => '1',
      'tel' => '<a href="tel:999-999-9999">999-999-9999</a>',
      'url' => '<a href="http://example.com">http://example.com</a>',
      'color' => '<span style="display:inline-block; height:1em; width:1em; border:1px solid #000; background-color:#ffffcc"></span> #ffffcc',
      'weight' => '0',
      'date' => 'Tuesday, August 18, 2009',
      'datetime' => 'Tuesday, August 18, 2009 - 4:00 PM',
      'datelist' => 'Tuesday, August 18, 2009 - 4:00 PM',
      'dollars' => '$100.00',
      'text_format' => '<p>The quick brown fox jumped over the lazy dog.</p>',
      'entity_autocomplete (user)' => '<a href="' . $account->toUrl()->setAbsolute(TRUE)->toString() . '" hreflang="en">admin</a>',
      'language_select' => 'English (en)',
    ];
    foreach ($elements as $label => $value) {
      $this->assertRaw('<b>' . $label . '</b><br/>' . $value, new FormattableMarkup('Found @label: @value', ['@label' => $label, '@value' => $value]));
    }

    // Check details element.
    $this->assertRaw('<summary role="button" aria-expanded="true" aria-pressed="true">Standard Elements</summary>');

    // Check empty details element removed.
    $this->assertNoRaw('<summary role="button" aria-expanded="true" aria-pressed="true">Markup Elements</summary>');
  }

}

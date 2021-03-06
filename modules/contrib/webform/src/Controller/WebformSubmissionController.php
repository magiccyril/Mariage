<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform submissions.
 */
class WebformSubmissionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a new WebformSubmissionController object.
   *
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(WebformRequestInterface $request_handler) {
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.request')
    );
  }

  /**
   * Returns a webform submission in a specified format type.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $type
   *   The format type.
   *
   * @return array
   *   A render array representing a webform submission in a specified format
   *   type.
   */
  public function index(WebformSubmissionInterface $webform_submission, $type) {
    if ($type == 'default') {
      $type = 'html';
    }

    $build = [];
    $source_entity = $this->requestHandler->getCurrentSourceEntity('webform_submission');
    // Navigation.
    $build['navigation'] = [
      '#theme' => 'webform_submission_navigation',
      '#webform_submission' => $webform_submission,
    ];

    // Information.
    $build['information'] = [
      '#theme' => 'webform_submission_information',
      '#webform_submission' => $webform_submission,
      '#source_entity' => $source_entity,
    ];

    // Submission.
    $build['submission'] = [
      '#theme' => 'webform_submission_' . $type,
      '#webform_submission' => $webform_submission,
      '#source_entity' => $source_entity,
    ];

    // Wrap plain text and YAML in CodeMirror view widget.
    if (in_array($type, ['text', 'yaml'])) {
      $build['submission'] = [
        '#theme' => 'webform_codemirror',
        '#code' => $build['submission'],
        '#type' => $type,
      ];
    }

    $build['#attached']['library'][] = 'webform/webform.admin';

    return $build;
  }

  /**
   * Toggle webform submission sticky.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response that toggle the sticky icon.
   */
  public function sticky(WebformSubmissionInterface $webform_submission) {
    // Toggle sticky.
    $webform_submission->setSticky(!$webform_submission->isSticky())->save();

    // Get state.
    $state = $webform_submission->isSticky() ? 'on' : 'off';

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(
      '#webform-submission-' . $webform_submission->id() . '-sticky',
      new FormattableMarkup('<span class="webform-icon webform-icon-sticky webform-icon-sticky--@state"></span>', ['@state' => $state])
    ));
    return $response;

  }

  /**
   * Route title callback.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   *
   * @return array
   *   The webform submission as a render array.
   */
  public function title(WebformSubmissionInterface $webform_submission) {
    $source_entity = $this->requestHandler->getCurrentSourceEntity('webform_submission');
    $t_args = [
      '@form' => ($source_entity) ? $source_entity->label() : $webform_submission->getWebform()->label(),
      '@id' => $webform_submission->serial(),
    ];
    return $this->t('@form: Submission #@id', $t_args);
  }

}

<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Controller for webform handlers.
 */
class WebformEntityHandlersForm extends EntityForm {

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $user_input = $form_state->getUserInput();

    // Build table header.
    $header = [
      $this->t('Title / Description'),
      $this->t('ID'),
      $this->t('Summary'),
      $this->t('Status'),
      $this->t('Weight'),
      $this->t('Operations'),
    ];

    // Build table rows for handlers.
    $handlers = $this->entity->getHandlers();
    $rows = [];
    foreach ($handlers as $handler) {
      $key = $handler->getHandlerId();
      $rows[$key]['#attributes']['class'][] = 'draggable';

      $rows[$key]['#weight'] = isset($user_input['handlers']) ? $user_input['handlers'][$key]['weight'] : NULL;

      $rows[$key]['handler'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#markup' => '<b>' . $handler->label() . '</b>: ' . $handler->description(),
          ],
        ],
      ];

      $rows[$key]['id'] = [
        'data' => ['#markup' => $handler->getHandlerId()],
      ];

      $rows[$key]['summary'] = $handler->getSummary();

      $rows[$key]['status'] = [
        'data' => ['#markup' => ($handler->isEnabled()) ? $this->t('Enabled') : $this->t('Disabled')],
      ];

      $rows[$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $handler->label()]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $handler->getWeight(),
        '#attributes' => [
          'class' => ['webform-handler-order-weight'],
        ],
      ];

      $rows[$key]['operations'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('entity.webform.handler.edit_form', [
              'webform' => $this->entity->id(),
              'webform_handler' => $key,
            ]),
            'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('entity.webform.handler.delete_form', [
              'webform' => $this->entity->id(),
              'webform_handler' => $key,
            ]),
          ],
        ],
      ];
    }

    // Must manually add local actions to the webform because we can't alter local
    // actions and add the needed dialog attributes.
    // @see https://www.drupal.org/node/2585169
    $dialog_attributes = WebformDialogHelper::getModalDialogAttributes(
      800,
      ['button', 'button-action', 'button--primary', 'button--small']
    );
    $form['local_actions'] = [
      'add_element' => [
        '#type' => 'link',
        '#title' => $this->t('Add email'),
        '#url' => new Url('entity.webform.handler.add_form', ['webform' => $webform->id(), 'webform_handler' => 'email']),
        '#attributes' => $dialog_attributes,
        'add_page' => [
          '#type' => 'link',
          '#title' => $this->t('Add handler'),
          '#url' => new Url('entity.webform.handlers', ['webform' => $webform->id()]),
          '#attributes' => $dialog_attributes,
        ],
      ],
    ];

    // Build the list of existing webform handlers for this webform.
    $form['handlers'] = [
      '#type' => 'table',
      '#header' => $header,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'webform-handler-order-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'webform-handlers',
      ],
      '#empty' => $this->t('There are currently no handlers setup for this webform.'),
    ] + $rows;

    $form['#attached']['library'][] = 'webform/webform.admin';

    // Must preload CKEditor and CodeMirror library so that the
    // window.dialog:aftercreate trigger is set before any dialogs are opened.
    // @see js/webform.element.codemirror.js
    $form['#attached']['library'][] = 'webform/webform.element.codemirror.yaml';
    $form['#attached']['library'][] = 'webform/webform.element.html_editor';

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $form = parent::actionsElement($form, $form_state);
    $form['submit']['#value'] = $this->t('Save handlers');
    unset($form['delete']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update webform handler weights.
    if (!$form_state->isValueEmpty('handlers')) {
      $this->updateHandlerWeights($form_state->getValue('handlers'));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $webform->save();

    $this->logger('webform')->notice('Webform @label handler saved.', ['@label' => $webform->label()]);
    drupal_set_message($this->t('Webform %label handler saved.', ['%label' => $webform->label()]));
  }

  /**
   * Updates webform handler weights.
   *
   * @param array $handlers
   *   Associative array with handlers having handler ids as keys and array
   *   with handler data as values.
   */
  protected function updateHandlerWeights(array $handlers) {
    foreach ($handlers as $handler_id => $handler_data) {
      if ($this->entity->getHandlers()->has($handler_id)) {
        $this->entity->getHandler($handler_id)->setWeight($handler_data['weight']);
      }
    }
  }

}

<?php

namespace Drupal\onlyoffice_docspace\Form;

/**
 * Copyright (c) Ascensio System SIA 2023.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager;
use Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the ONLYOFFICE DocSpace users export form.
 */
class UsersForm extends FormBase {

  /**
   * The ONLYOFFICE DocSpace Request manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface
   */
  protected $requestManager;

  /**
   * The ONLYOFFICE DocSpace Component manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager
   */
  protected $componentManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The term list builder.
   *
   * @var \Drupal\Core\Entity\EntityListBuilderInterface
   */
  protected $userListBuilder;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * An array of actions that can be executed.
   *
   * @var \Drupal\system\ActionConfigEntityInterface[]
   */
  protected $actions;

  /**
   * Constructs a \Drupal\onlyoffice_docspace\UsersForm object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   * @param \Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface $request_manager
   *   The ONLYOFFICE DocSpace Request manager.
   * @param \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager $component_manager
   *   The ONLYOFFICE DocSpace Component manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(ContainerInterface $container, RequestManagerInterface $request_manager,  ComponentManager $component_manager, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    $this->requestManager = $request_manager;
    $this->componentManager = $component_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->userListBuilder = OODSPUserListBuilder::createInstance($container, $entity_type_manager->getDefinition("user"));
    $this->messenger = $messenger;
    $this->logger = $this->getLogger('onlyoffice_docspace');

    $this->actions = array_filter($entity_type_manager->getStorage('action')->loadMultiple(), function ($action) {
      return $action->getType() == 'onlyoffice_docspace_user';
    });
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('onlyoffice_docspace.request_manager'),
      $container->get('onlyoffice_docspace.component_manager'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onlyoffice_docspace_users';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->userListBuilder->render();

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply to selected items'),
    ];

    $form['header'] = [
      '#type' => 'container',
      '#weight' => -100,
      '#markup' => '<span>' . $this->t('To add new users to ONLYOFFICE DocSpace select multiple users and press <b>Invite to DocSpace</b>. All new users will be added with <b>User</b> role, if you want to change the role go to Accounts. Role <b>Room admin</b> is paid!') . '</span>',
    ];

    $form['header']['user_bulk_form'] = [
      '#type' => 'container',
    ];
    $form['header']['user_bulk_form']['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#options' => $this->getBulkOptions(),
    ];

    $form['header']['user_bulk_form']['actions'] = $form['actions'];
    $form['pager'] = [
      '#type' => 'pager',
      '#weight' => 100,
    ];

    $form['oodsp-system-hidden-block'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['hidden'],
      ],
      'div' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'id' => 'oodsp-system-frame',
        ],
      ],
    ];

    $form['loader'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'onlyoffice-docspace-loader',
        'class' => ['ui-widget-overlay'],
        'hidden' => TRUE,
      ],
      'div' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['loader'],
        ],
      ],
    ];

    $form = $this->componentManager->buildComponent($form, $this->currentUser());

    $form['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.users';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ids = $form_state->getValue('onlyoffice_docspace_users');
    if (empty($ids) || empty(array_filter($ids))) {
      $form_state->setErrorByName('', $this->t('No users selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $selected = array_filter($user_input['onlyoffice_docspace_users']);
    $action = $this->actions[$form_state->getValue('action')];
    $userStorage = $this->entityTypeManager->getStorage('user');

    $executionData = [];
    $count = 0;

    foreach ($selected as $bulk_form_key) {
      $bulk_form_key = explode('$$', $bulk_form_key, 2);

      $id = $bulk_form_key[0];
      $passwordHash = $bulk_form_key[1];

      $entity = $userStorage->load($id);

      if (empty($entity)) {
        continue;
      }

      if (!$action->getPlugin()->access($entity, $this->currentUser())) {
        $this->messenger->addError($this->t('No access to execute %action on the @entity_type_label %entity_label.', [
          '%action' => $action->label(),
          '@entity_type_label' => $entity->getEntityType()->getLabel(),
          '%entity_label' => $entity->label(),
        ]));
        continue;
      }

      $count++;

      $data = [
        'entity' => $entity,
        'passwordHash' => $passwordHash,
      ];
      array_push($executionData, $data);
    }

    if (!$count) {
      return;
    }

    $action->execute($executionData);

    $operation_definition = $action->getPluginDefinition();
    if (!empty($operation_definition['confirm_form_route_name'])) {
      $options = [
        'query' => $this->getDestinationArray(),
      ];
      $form_state->setRedirect($operation_definition['confirm_form_route_name'], [], $options);
    }
    else {
      $this->messenger->addStatus($this->formatPlural($count, '%action was applied to @count item.', '%action was applied to @count items.', [
        '%action' => $action->label(),
      ]));
    }
  }

  /**
   * Returns the available operations for this form.
   */
  protected function getBulkOptions() {
    $options = [];

    foreach ($this->actions as $id => $action) {
      $options[$id] = $action->label();
    }

    return $options;
  }

}

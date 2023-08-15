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

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\onlyoffice_docspace\Controller\OODSPCredentialsController;
use Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager;
use Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface;
use Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure ONLYOFFICE Connector settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The request manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface
   */
  protected $requestManager;

  /**
   * The ONLYOFFICE DocSpace Security manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface
   */
  protected $securityManager;

  /**
   * The ONLYOFFICE DocSpace Component manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager
   */
  protected $componentManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a \Drupal\onlyoffice_docspace\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface $request_manager
   *   The aggregator fetcher plugin manager.
   * @param \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface $security_manager
   *   The ONLYOFFICE DocSpace Security manager.
   * @param \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager $component_manager
   *   The ONLYOFFICE DocSpace Component manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestManagerInterface $request_manager, SecurityManagerInterface $security_manager, ComponentManager $component_manager) {
    parent::__construct($config_factory);
    $this->requestManager = $request_manager;
    $this->securityManager = $security_manager;
    $this->componentManager = $component_manager;
    $this->logger = $this->getLogger('onlyoffice_docspace');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('onlyoffice_docspace.request_manager'),
      $container->get('onlyoffice_docspace.security_manager'),
      $container->get('onlyoffice_docspace.component_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onlyoffice_docspace_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['onlyoffice_docspace.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form = $this->componentManager->buildComponent($form, $this->currentUser());

    $form['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.settings';

    $url = $this->config('onlyoffice_docspace.settings')->get('url');
    $login = $this->config('onlyoffice_docspace.settings')->get('login');
    $passwordHash = $this->config('onlyoffice_docspace.settings')->get('passwordHash');

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DocSpace Service Address'),
      '#default_value' => $url,
      '#required' => TRUE,
    ];
    $form['login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login'),
      '#default_value' => $login,
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    $form['passwordHash'] = [
      '#type' => 'hidden',
      '#default_value' => NULL,
    ];

    $form['currentUserPasswordHash'] = [
      '#type' => 'hidden',
      '#default_value' => NULL,
    ];

    $form['oodsp-settings-hidden-block'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['hidden'],
      ],
    ];

    $form['oodsp-settings-hidden-block']['div'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'oodsp-settings-frame',
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

    $form['message_users'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'warning' =>  [$this->t('The current user will be added to DocSpace with the <b>Room admin</b> role. <b>WordPress Viewer</b> user will be added to DocSpace with View Only access.')]
      ],
      '#status_headings' => [
        'warning' => $this->t('Warning message'),
      ],
    ];

    $form = parent::buildForm($form, $form_state);

    if (!empty($url) && !empty($login) && !empty($passwordHash)) {
      $form['export_users'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('DocSpace Users'),
        'description' => [
          '#markup' => '<p>' . $this->t('To add new users to ONLYOFFICE DocSpace and to start working in plugin, please press') . ' <b>' . $this->t('Export Now') . '</b></p>' ,
        ],
        '#weight' => 100,
      ];

      $urlUsersForm = Url::fromRoute('onlyoffice_docspace.users_form');
      $url_options = [
        'attributes' => [
          'class' => [
            'button',
          ],
        ],
      ];
      $urlUsersForm->setOptions($url_options);

      $form['export_users']['export'] = [
        '#type' => 'link',
        '#title' => $this->t('Export Now'),
        '#url' => $urlUsersForm,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('url');
    $login = $form_state->getValue('login');
    $passwordHash = $form_state->getValue('passwordHash');

    $url = '/' === substr($url, -1) ? $url : $url . '/';

    $responseConnect = $this->requestManager->connectDocSpace($url, $login, $passwordHash);

    if ($this->requestManager::UNAUTHORIZED === $responseConnect['error']) {
      $form_state->setErrorByName('', $this->t('Invalid credentials. Please try again!'));
    }
    if ($this->requestManager::USER_NOT_FOUND === $responseConnect['error']) {
      $form_state->setErrorByName('', $this->t('Error getting data user. Please try again!'));
    }
    if ($this->requestManager::FORBIDDEN === $responseConnect['error']) {
      $form_state->setErrorByName('', $this->t('The specified user is not a DocSpace administrator!'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('onlyoffice_docspace.settings')
      ->set('url', rtrim($form_state->getValue('url'), '/'))
      ->set('login', $form_state->getValue('login'))
      ->set('passwordHash', $form_state->getValue('passwordHash'))
      ->save();
    parent::submitForm($form, $form_state);

    $url = $form_state->getValue('url');
    $url = '/' === substr($url, -1) ? $url : $url . '/';
    $token = $this->config('onlyoffice_docspace.settings')->get('token');

    $responseCreatePublicUser = $this->requestManager->createPublicUser($url, $token);

    if ($responseCreatePublicUser['error'] === $this->requestManager::ERROR_USER_INVITE) {
      $this->messenger()->addWarning($this->t('Public DocSpace user was not created! View content will not be available on public pages.'));
    } elseif ($responseCreatePublicUser['error'] === $this->requestManager::ERROR_SET_USER_PASS) {
      $responseDocSpaceUser = $this->requestManager->getDocSpaceUser($url, OODSPCredentialsController::OODSP_PUBLIC_USER_LOGIN, $token);

      if (!$responseDocSpaceUser['error'] ) {
        $this->config('onlyoffice_docspace.settings')->set('publicUserId', $responseDocSpaceUser['data']['id'])->save();
      }
      $this->messenger()->addWarning($this->t('Public DocSpace user already created, but failed to update authorization.'));
    } elseif ($responseCreatePublicUser['error']) {
      $this->messenger()->addWarning($this->t('Public DocSpace user was not created. View content will not be available on public pages.'));
    } else {
        $this->config('onlyoffice_docspace.settings')->set('publicUserId', $responseCreatePublicUser['data']['id'])->save();
        $this->messenger()->addStatus($this->t('Public DocSpace user successfully created.'));
    }

    $currentUser = $this->currentUser();

    $responseDocSpaceUser = $this->requestManager->getDocSpaceUser($url, $currentUser->getEmail(), $token);

    if ($responseDocSpaceUser['error']) {
      $responseInviteToDocSpace = $this->requestManager->inviteToDocSpace(
        $currentUser->getEmail(),
        $form_state->getValue('currentUserPasswordHash'),
        '',
        '',
        1, // Room Admin.
        $token
      );

      if ($responseInviteToDocSpace['error']) {
        $this->messenger()->addError($this->t('Error create user @user_email in DocSpace!', ['@user_email' => $currentUser->getEmail()]));
      } else {
        $this->securityManager->setPasswordHash($currentUser->id(), $form_state->getValue('currentUserPasswordHash'));
        $this->messenger()->addStatus($this->t('User @user_email successfully created in DocSpace with role Room Admin.', ['@user_email' => $currentUser->getEmail()]));
      }
    } else {
      $this->messenger()->addWarning($this->t('User @user_email already exists in DocSpace!', ['@user_email' => $currentUser->getEmail()]));
    }
  }

  
}

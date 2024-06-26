<?php

namespace Drupal\onlyoffice_docspace\Form;

/**
 * Copyright (c) Ascensio System SIA 2024.
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

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface;
use Drupal\onlyoffice_docspace\Manager\UtilsManager\UtilsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the ONLYOFFICE DocSpace users export form.
 */
class LoginForm extends FormBase {

  /**
   * The ONLYOFFICE DocSpace Security manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface
   */
  protected $securityManager;

  /**
   * The ONLYOFFICE DocSpace Utils manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\UtilsManager\UtilsManager
   */
  protected $utilsManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a \Drupal\onlyoffice_docspace\UsersForm object.
   *
   * @param \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface $security_manager
   *   The ONLYOFFICE DocSpace Security manager.
   * @param \Drupal\onlyoffice_docspace\Manager\UtilsManager\UtilsManager $utils_manager
   *   The ONLYOFFICE DocSpace Utils manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(SecurityManagerInterface $security_manager, UtilsManager $utils_manager, MessengerInterface $messenger, ModuleExtensionList $extension_list_module, RequestStack $request_stack) {
    $this->securityManager = $security_manager;
    $this->utilsManager = $utils_manager;
    $this->messenger = $messenger;
    $this->extensionListModule = $extension_list_module;
    $this->requestStack = $request_stack;
    $this->logger = $this->getLogger('onlyoffice_docspace');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('onlyoffice_docspace.security_manager'),
      $container->get('onlyoffice_docspace.utils_manager'),
      $container->get('messenger'),
      $container->get('extension.list.module'),
      $container->get('request_stack'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onlyoffice_docspace_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $redirect = $this->requestStack->getCurrentRequest()->query->get('redirect');

    $form = [];
    $form = $this->utilsManager->buildComponent($form, $this->currentUser());

    $form['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.login';

    $form['login-form'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Drupal requests access to your ONLYOFFICE DocSpace') . ' <span class="url-host">' . parse_url($this->config('onlyoffice_docspace.settings')->get('url'), PHP_URL_HOST) . '</span>',
    ];

    $form['login-form']['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['center'],
      ],
    ];

    $form['login-form']['header']['drupal_logo'] = [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/login-drupal.svg',
      ],
    ];

    $form['login-form']['header']['union'] = [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/union.svg',
      ],
    ];

    $form['login-form']['header']['onlyoffice_logo'] = [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/login-onlyoffice.svg',
      ],
    ];

    $form['login-form']['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Your account <b>@user_email</b> will be synced with your DocSpace. Please enter your DocSpace password in the field below:', ['@user_email' => $this->currentUser()->getEmail()]),
      '#size' => 60,
      '#required' => TRUE,
    ];

    $form['login-form']['passwordHash'] = [
      '#type' => 'hidden',
      '#default_value' => NULL,
    ];

    if (!empty($redirect)) {
      $form['login-form']['redirect'] = [
        '#type' => 'hidden',
        '#default_value' => Url::fromUserInput('/' . ltrim($redirect, '/'))->toString(),
      ];
    }

    $form['login-form']['actions'] = ['#type' => 'actions'];
    $form['login-form']['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Log in'),
    ];

    $form['oodsp-login-hidden-block'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['hidden'],
      ],
      'div' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'id' => 'oodsp-login-frame',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('passwordHash'))) {
      $form_state->setErrorByName('pass', $this->t('Password is empty!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $passwordHash = $form_state->getValue('passwordHash');

    $this->securityManager->setPasswordHash($this->currentUser()->id(), $passwordHash);

    try {
      $redirect = $form_state->getValue('redirect');

      if (!empty($redirect)) {
        $form_state->setRedirectUrl(Url::fromUserInput('/' . ltrim($redirect, '/')));
      }
      else {
        $form_state->setRedirect('onlyoffice_docspace.page');
      }
    }
    catch (\Exception $e) {
      $form_state->setRedirect('onlyoffice_docspace.page');
    }
  }

}

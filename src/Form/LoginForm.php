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

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager;
use Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The ONLYOFFICE DocSpace Component manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager
   */
  protected $componentManager;

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
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a \Drupal\onlyoffice_docspace\UsersForm object.
   *
   * @param \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface $security_manager
   *   The aggregator fetcher plugin manager.
   * @param \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager $component_manager
   *   The ONLYOFFICE DocSpace Component manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(SecurityManagerInterface $security_manager, ComponentManager $component_manager, MessengerInterface $messenger, ModuleExtensionList $extension_list_module) {
    $this->securityManager = $security_manager;
    $this->componentManager = $component_manager;
    $this->messenger = $messenger;
    $this->extensionListModule = $extension_list_module;
    $this->logger = $this->getLogger('onlyoffice_docspace');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('onlyoffice_docspace.security_manager'),
      $container->get('onlyoffice_docspace.component_manager'),
      $container->get('messenger'),
      $container->get('extension.list.module'),
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
    $form = [];
    $form = $this->componentManager->buildComponent($form, $this->currentUser());

    $form['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.login';
    
    $form['login-form'] = [
      '#type' => 'fieldset',
      '#title' => 'Drupal requests access to your ONLYOFFICE DocSpace worker.onlyoffice.com'
    ];

    $form['login-form']['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
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
      '#title' => $this->t('Your account example@email.com will be synced with your DocSpace. Please enter your DocSpace password in the field below:'),
      '#size' => 60,
      '#required' => TRUE,
    ];

    $form['login-form']['passwordHash'] = [
      '#type' => 'hidden',
      '#default_value' => NULL,
    ];

    $form['login-form']['actions'] = ['#type' => 'actions'];
    $form['login-form']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in')
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

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $passwordHash = $form_state->getValue('passwordHash');

    $this->securityManager->setPasswordHash($this->currentUser()->id(), $passwordHash);

    $form_state->setRedirect('onlyoffice_docspace.page');
  }
}

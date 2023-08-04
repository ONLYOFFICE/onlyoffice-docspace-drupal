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

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface;

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
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestManagerInterface $request_manager) {
    parent::__construct($config_factory);
    $this->requestManager = $request_manager;
    $this->logger = $this->getLogger('onlyoffice');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('onlyoffice_docspace.request_manager'),
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
    $form['#attached'] = [
      'library' => ['onlyoffice_docspace/onlyoffice_docspace.settings'],
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DocSpace Service Address'),
      '#default_value' => $this->config('onlyoffice_docspace.settings')->get('url'),
      '#required' => TRUE,
    ];
    $form['login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login'),
      '#default_value' => $this->config('onlyoffice_docspace.settings')->get('login'),
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    $form['passwordHash'] = [
      '#type' => 'hidden',
      '#default_value' => NULL
    ];

    $form['oodsp-settings-hidden-block'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['hidden']
      ],
    ];

    $form['oodsp-settings-hidden-block']['div'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'oodsp-settings-frame'
      ]
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
          'class' => ['loader']
        ],
      ]
    ];

    $form = parent::buildForm($form, $form_state);

    $form['actions']['export_users'] = [
      '#type' => 'fieldset',
      '#title' =>  $this->t('DocSpace Users'),
      'description' => [
        '#markup' => '<p>' . $this->t('To add new users to ONLYOFFICE DocSpace and to start working in plugin, please press Export Now. Users who don’t have an account in DocSpace will have Drupal Viewer with View Only access to content.') . '</p>',
      ],
    ];

    $url = Url::fromRoute('onlyoffice_docspace.user_form');
    $url_options = [
      'attributes' => [
        'class' => [
          'button',
        ],
      ],
    ];
    $url->setOptions($url_options);

    $form['actions']['export_users']['export'] = [
      '#type' => 'link',
      '#title' => $this->t('Export Now'),
      '#url'=> $url
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $url = $form_state->getValue('url');
    $login = $form_state->getValue('login');
    $passwordHash = $form_state->getValue('passwordHash');

    $url = '/' === substr( $url, -1 ) ? $url : $url . '/';

    $responseConnect =$this->requestManager->connectDocSpace($url, $login, $passwordHash);

		if ( $this->requestManager::UNAUTHORIZED === $responseConnect['error'] ) {
      $form_state->setErrorByName('', $this->t('Invalid credentials. Please try again!'));
		}
		if ( $this->requestManager::USER_NOT_FOUND === $responseConnect['error'] ) {
      $form_state->setErrorByName('', $this->t('Error getting data user. Please try again!'));
		}
		if ( $this->requestManager::FORBIDDEN === $responseConnect['error'] ) {
      $form_state->setErrorByName('', $this->t('The specified user is not a DocSpace administrator!'));
		}

    parent::validateForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('onlyoffice_docspace.settings')
      ->set('url', $form_state->getValue('url'))
      ->set('login', $form_state->getValue('login'))
      ->set('passwordHash', $form_state->getValue('passwordHash'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

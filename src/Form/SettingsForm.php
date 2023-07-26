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
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure ONLYOFFICE Connector settings for this site.
 */
class SettingsForm extends ConfigFormBase {

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
      '#default_value' => $this->config('onlyoffice_docspace.settings')->get('password'),
      '#required' => TRUE,
    ];

    $form['export_users'] = [
      '#type' => 'fieldset',
      '#title' =>  $this->t('DocSpace Users'),
      'description' => [
        '#markup' => '<p>' . $this->t('To add new users to ONLYOFFICE DocSpace and to start working in plugin, please press Export Now. Users who don’t have an account in DocSpace will have Drupal Viewer with View Only access to content.') . '</p>',
      ],
    ];

    $form['export_users']['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export Now')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('onlyoffice_docspace.settings')
      ->set('url', $form_state->getValue('url'))
      ->set('login', $form_state->getValue('login'))
      ->set('password', $form_state->getValue('password'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

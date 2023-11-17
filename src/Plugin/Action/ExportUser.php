<?php

namespace Drupal\onlyoffice_docspace\Plugin\Action;

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

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface;
use Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ONLYOFFICE DocSpace export user accounts.
 *
 * @Action(
 *   id = "onlyoffice_docspace_export_user_action",
 *   label = @Translation("Invite to DocSpace"),
 *   type = "onlyoffice_docspace_user",
 * )
 */
class ExportUser extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The ONLYOFFICE DocSpace request manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface
   */
  protected $requestManager;

  /**
   * The ONLYOFFICE DocSpace security manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface
   */
  protected $securityManager;

  /**
   * Constructs a OBLYOFFICE DocSpace ExportUser object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface $request_manager
   *   The ONLYOFFICE DocSpace request manager.
   * @param \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface $security_manager
   *   The ONLYOFFICE DocSpace security manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, RequestManagerInterface $request_manager, SecurityManagerInterface $security_manager) {
    $this->messenger = $messenger;
    $this->requestManager = $request_manager;
    $this->securityManager = $security_manager;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('onlyoffice_docspace.request_manager'),
      $container->get('onlyoffice_docspace.security_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $data) {
    $responseDocSpaceUsers = $this->requestManager->getDocSpaceUsers();

    if ($responseDocSpaceUsers['error']) {
      $this->messenger()->addError($this->t('Error getting users from ONLYOFFICE DocSpace'));
    }

    $listDocSpaceUsers = array_map(
      function ($user) {
        return $user['email'];
      },
      $responseDocSpaceUsers['data']
    );

    $countUsers = count($data);
    $countInvited = 0;
    $countSkipped = 0;
    $countError = 0;

    foreach ($data as $value) {
      $entity = $value['entity'];
      $passwordHash = $value['passwordHash'];

      if (in_array($entity->getEmail(), $listDocSpaceUsers, TRUE)) {
        $countSkipped++;
      }
      else {
        $firstName = $lastName = preg_replace("/[^\p{L}\p{M} \-]/u", "-", $entity->getAccountName());

        $responseInvite = $this->requestManager->inviteToDocSpace(
            $entity->getEmail(),
            $passwordHash,
            $firstName,
            $lastName,
            2
          );

        if ($responseInvite['error']) {
          $countError++;
        }
        else {
          $this->securityManager->setPasswordHash($entity->id(), $passwordHash);
          $countInvited++;
        }
      }
    }

    if ($countError !== 0) {
      $this->messenger()->addError(
        $this->t(
          'Invitation failed for @count_errors/@count_users user(s)',
          [
            '@count_errors' => $countError,
            '@count_users' => $countUsers,
          ]
        )
      );
    }

    if ($countSkipped !== 0) {
      $this->messenger()->addWarning(
        $this->t(
          'Invitation skipped for @count_skipped/@count_users user(s). User(s) with the indicated email(s) may already exist in DocSpace.',
          [
            '@count_skipped' => $countSkipped,
            '@count_users' => $countUsers,
          ]
        )
      );
    }

    if ($countInvited !== 0) {
      $this->messenger()->addStatus(
        $this->t(
          'Invitation successfully sent to @count_invited/@count_users user(s)',
          [
            '@count_invited' => $countInvited,
            '@count_users' => $countUsers,
          ]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    return $account->hasPermission('administer onlyoffice_docspace configuration');
  }

}

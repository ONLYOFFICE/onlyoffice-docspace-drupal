/*
 * (c) Copyright Ascensio System SIA 2024
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

(function ($, Drupal) {
  var $settingsForm = $('#onlyoffice-docspace-settings');

  $('input[name="passwordHash"]').val('');

  $('[data-drupal-messages-fallback]').show()
  const messages = new Drupal.Message(document.querySelector('[data-drupal-messages-fallback]'));

  $settingsForm.on('submit', function (event) {
    var passwordHash = $('input[name="passwordHash"]').val() || "";

    if (passwordHash.trim().length <= 0) {
      event.preventDefault();
      const url = $('#edit-url').val().trim();
      const password = $('#edit-password').val();

      DocspaceIntegrationSdk.initScript('oodsp-api-js', url)
        .then(async function () {
          Drupal.showLoader();

          DocSpace.SDK.initSystem({
            frameId: "oodsp-settings-frame",
            events: {
              "onAppReady": async function () {
                const hashSettings = await DocSpace.SDK.frames['oodsp-settings-frame'].getHashSettings();

                const hash = await DocSpace.SDK.frames['oodsp-settings-frame'].createHash(password, hashSettings);
                $('input[name="passwordHash"]').val(hash);

                const hashCurrentUser = await DocSpace.SDK.frames['oodsp-settings-frame'].createHash(generatePass(), hashSettings);
                $('input[name="currentUserPasswordHash"]').val(hashCurrentUser);

                $('#edit-password').val(randomString(password.length));

                $settingsForm.submit();
              },
              'onAppError': function (e) {
                Drupal.hideLoader();
                messages.clear();

                if ( e === "The current domain is not set in the Content Security Policy (CSP) settings." ) {
                  messages.add(
                    Drupal.t(
                      'The current domain is not set in the Content Security Policy (CSP) settings. Please add it via <a href=\"@developer_tools\" target=\"_blank\">the Developer Tools section</a>.',
                      {
                        '@developer_tools': stripTrailingSlash(url) + '/portal-settings/developer-tools/javascript-sdk'
                      }
                    ),
                    {type: 'error'}
                  );
                } else {
                  addNotice( e, 'error' );
                }
              }
            }
          });
        }).catch(
          function () {
            Drupal.hideLoader();
            messages.clear();
            messages.add(Drupal.t('ONLYOFFICE DocSpace cannot be reached.'), {type: 'error'});
          }
        );

      return false;
    } else {
      return true;
    }
  });

  const randomString = function (i) {
    var rnd = '';

    while (rnd.length < i) {
      rnd += Math.random().toString(36).substring(2);
    }

    return rnd.substring(0, i);
  };

  const generatePass = function () {
    var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()";
    var passwordLength = 24;
    var password = "";

    for (var i = 0; i <= passwordLength; i++) {
      var randomNumber = Math.floor( Math.random() * chars.length );
      password += chars.substring( randomNumber, randomNumber + 1 );
    }

    return password;
  };

  const stripTrailingSlash = ( str ) => {
    return str.endsWith( '/' )
        ? str.slice( 0, -1 )
        : str;
  };

})(jQuery, Drupal);

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
  const frameId = 'oodsp-login-frame';

  $loginForm = $('#onlyoffice-docspace-login');

  DocspaceIntegrationSdk.initScript('oodsp-api-js', drupalSettings.OODSP_Settings.url)
    .then(function (e) {
      DocSpace.SDK.initSystem(
        {
          frameId: frameId,
          events: {
            onAppReady: async function () {
              const userInfo = await DocSpace.SDK.frames[frameId].getUserInfo();
              if (userInfo && userInfo.email === drupalSettings.OODSP_Settings.currentUser) {
                var redirect = drupalSettings.OODSP_Settings.adminUrl;

                if ($loginForm.find('input[name="redirect"]').length > 0) {
                  redirect = loginForm.find('input[name="redirect"]').val();
                }

                window.location.replace(redirect);
              }
            }
          }
        }
      );
    }).catch(function () {
      window.location.replace(drupalSettings.OODSP_Settings.adminUrl);
    });

  const messages = new Drupal.Message();

  $('input[name="passwordHash"]').val('');
  $loginForm.on('submit', async function (event) {
    var passwordHash = $('input[name="passwordHash"]').val() || "";

    if (passwordHash.trim().length <= 0) {
      event.preventDefault();
      const pass = $('#edit-pass').val();
      const hashSettings = await DocSpace.SDK.frames[frameId].getHashSettings();
      const hash = await DocSpace.SDK.frames[frameId].createHash(pass.trim(), hashSettings);

      if (hash === null || hash.length === "") {
        messages.clear();
        messages.add(Drupal.t('User authentication failed.'), {type: 'error'});
      }

      DocSpace.SDK.frames[frameId].login(drupalSettings.OODSP_Settings.currentUser, hash)
        .then(function (response) {
              if(response.status && response.status !== 200) {
                messages.clear();
                messages.add(Drupal.t('User authentication failed.'), {type: 'error'});
              } else {
                $('input[name="passwordHash"]').val(hash);
                $('#edit-pass').val(randomString(pass.length));
                $loginForm.submit();
              }
          }
      );
    }
  });

  const randomString = function (i) {
    var rnd = '';

    while (rnd.length < i) {
      rnd += Math.random().toString(36).substring(2);
    }

    return rnd.substring(0, i);
  };
})(jQuery, Drupal);

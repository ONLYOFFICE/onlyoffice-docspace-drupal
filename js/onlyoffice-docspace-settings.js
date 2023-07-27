/*
 * (c) Copyright Ascensio System SIA 2023
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

  $settingsForm.on('submit', function(event) {
    const messages = new Drupal.Message();

    var passwordHash = $('input[name="passwordHash"]').val() || "";

    if (passwordHash.trim().length <= 0) {
      const url = $('#edit-url').val().trim();
      const password = $('#edit-password').val();

      DocSpaceComponent.initScript(url)
        .then(async function() {
          showLoader();

          DocSpace.SDK.initSystem({
            frameId: "oodsp-settings-frame",
            events: {
              "onAppReady": async function() {
                const hashSettings = await DocSpace.SDK.frames['oodsp-settings-frame'].getHashSettings();
                const hash  = await DocSpace.SDK.frames['oodsp-settings-frame'].createHash(password, hashSettings);

                $('input[name="passwordHash"]').val(hash);
                $('#edit-password').val(randomString(password.length));

                $settingsForm.submit();
              },
              'onAppError': function() {
                hideLoader();
                messages.clear();
                messages.add('ONLYOFFICE DocSpace cannot be reached.', {type: 'error'});
              }
            }
          });
        }).catch(
          function() {
            hideLoader();
            messages.clear();
            messages.add('ONLYOFFICE DocSpace cannot be reached.', {type: 'error'});
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

  const showLoader = function() {
       $('#oodsp-settings-loader').show();
    };

    const hideLoader = function() {
        $('#oodsp-settings-loader').hide();
    };

})(jQuery, Drupal);

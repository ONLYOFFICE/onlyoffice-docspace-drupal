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
  DocSpaceComponent.initScript()
			.then(
				function(e) {
					DocSpace.SDK.initSystem(
						{
							frameId: 'oodsp-system-frame'
						}
					);
				}
			);

  var $usersForm = $('#onlyoffice-docspace-users');
  $usersForm.removeAttr('hashGenerated');

  $usersForm.on('submit', async function(event) {
    if ($('#edit-action').val() === 'onlyoffice_docspace_export_user_action' && !$usersForm.attr('hashGenerated')) {
      event.preventDefault();
      Drupal.showLoader();

      const hashSettings = await DocSpace.SDK.frames['oodsp-system-frame'].getHashSettings();

      const $users = $('td.table-select input');

      for (var user of $users) {
        if ($(user).is(':checked')) {
          const hash = await DocSpace.SDK.frames['oodsp-system-frame'].createHash(generatePass(), hashSettings);

          $(user).val($(user).val() + "$$" + hash);
        }
      }

      $usersForm.attr('hashGenerated', true);
      $usersForm.submit();
    }
  });

  const generatePass = function() {
		var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()";
		var passwordLength = 24;
		var password = "";

		for (var i = 0; i <= passwordLength; i++) {
			var randomNumber = Math.floor(Math.random() * chars.length);
			password += chars.substring(randomNumber, randomNumber + 1);
		}

		return password;
	}

})(jQuery, Drupal);

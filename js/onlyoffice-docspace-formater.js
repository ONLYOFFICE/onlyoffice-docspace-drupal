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

(function (Drupal) {
  DocspaceIntegrationSdk.initScript("oodsp-api-js", drupalSettings.OODSP_Settings.url).then(
    function() {
      for (var frameId in drupalSettings.OODSPFormatterData) {
        drupalSettings.OODSPFormatterData[frameId]['width'] = "100%";
        drupalSettings.OODSPFormatterData[frameId]['height'] = "100%";
        drupalSettings.OODSPFormatterData[frameId].locale = drupalSettings.OODSP_Settings.locale;
        if (drupalSettings.OODSP_Settings.isAnonymous) {
          if (! drupalSettings.OODSPFormatterData[frameId].hasOwnProperty('requestToken')
                || drupalSettings.OODSPFormatterData[frameId].requestToken.length <= 0) {
            Drupal.OODSP_Utils.renderError(frameId, {
              header: drupalSettings.OODSP_Settings.messages.unauthorizedHeader,
              message: drupalSettings.OODSP_Settings.messages.unauthorizedMessage,
            });
            continue;
          }

          DocspaceIntegrationSdk.logout(
            frameId,
            function() {
              DocSpace.SDK.initFrame(drupalSettings.OODSPFormatterData[frameId]);
            }
          );
          continue;
        }

        DocspaceIntegrationSdk.loginByPasswordHash(
          frameId,
          drupalSettings.OODSP_Settings.currentUser,
          function () {
            return Drupal.OODSP_Utils.getPasswordHash()
          },
          function () {
            DocSpace.SDK.initFrame(drupalSettings.OODSPFormatterData[frameId]);
          },
          function () {
            if (! drupalSettings.OODSPFormatterData[frameId].hasOwnProperty('requestToken')
                  || drupalSettings.OODSPFormatterData[frameId].requestToken.length <= 0) {
              Drupal.OODSP_Utils.renderError(frameId, {
                header: drupalSettings.OODSP_Settings.messages.unauthorizedHeader,
                message: drupalSettings.OODSP_Settings.messages.unauthorizedMessage,
              });
              return;
            }

            DocspaceIntegrationSdk.logout(
              frameId,
              function () {
                DocSpace.SDK.initFrame( config );
              }
            );
          }
        );
      }
    }
  ).catch(
    function() {
      for (var frameId in drupalSettings.OODSPFormatterData) {
        Drupal.OODSP_Utils.renderError(frameId, {message: drupalSettings.OODSP_Settings.messages.docspaceUnavailable});
      }
    }
  );

})(Drupal);

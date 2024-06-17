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
  const defaultConfig = {
    width: "100%",
    height: "100%",
    locale: drupalSettings.OODSP_Settings.locale,
  };

  DocspaceIntegrationSdk.initScript("oodsp-api-js", drupalSettings.OODSP_Settings.url).then(
    function() {
      const firstElement = Object.values(drupalSettings.OODSPFormatterData)[0];

      for (var frameId in drupalSettings.OODSPFormatterData) {
        drupalSettings.OODSPFormatterData[frameId] = Object.assign( drupalSettings.OODSPFormatterData[frameId], defaultConfig );

        if (firstElement.frameId == frameId) {
          if (drupalSettings.OODSP_Settings.isAnonymous) {
            DocspaceIntegrationSdk.logout(
              firstElement.frameId,
              function () {
                _initAllFrames(Object.values(drupalSettings.OODSPFormatterData), true);
              }
            );
          } else {
            DocspaceIntegrationSdk.loginByPasswordHash(
              firstElement.frameId,
              drupalSettings.OODSP_Settings.currentUser,
              function () {
                return Drupal.OODSP_Utils.getPasswordHash()
              },
              function () {
                _initAllFrames(Object.values(drupalSettings.OODSPFormatterData), false);
              },
              function () {
                DocspaceIntegrationSdk.logout(
                  firstElement.frameId,
                  function () {
                    _initAllFrames(Object.values(drupalSettings.OODSPFormatterData), true);
                  }
                );
              }
            );
          }
        } else {
          DocSpace.SDK.initSystem({
              frameId: frameId,
              width: "100%",
              height: "100%",
              waiting: true
          });
        }
      }
    }
  ).catch(
    function(e) {
      for (var frameId in drupalSettings.OODSPFormatterData) {
        Drupal.OODSP_Utils.renderError(frameId, {message: drupalSettings.OODSP_Settings.messages.docspaceUnavailable});
      }
    }
  );

  const _initAllFrames = (oodspConfigs, requiredRequestToken) => {
    for (var config of oodspConfigs) {
      if (requiredRequestToken
        && (!config.hasOwnProperty('requestToken') || config.requestToken.length <= 0)) {

        if (DocSpace.SDK.frames[config.frameId] != null) {
          DocSpace.SDK.frames[config.frameId].destroyFrame();
        }

        Drupal.OODSP_Utils.renderError(config.frameId, {
          header: drupalSettings.OODSP_Settings.messages.unauthorizedHeader,
          message: drupalSettings.OODSP_Settings.messages.unauthorizedMessage,
        });

        continue;
      }

      DocSpace.SDK.frames[config.frameId].initFrame(config);
    }
  }

})(Drupal);

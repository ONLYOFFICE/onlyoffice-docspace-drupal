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

  const oodspConfigs = [];
  const oodspPublicConfigs = [];

  for (var frameId in drupalSettings.OODSPFormatterData) {
    if (drupalSettings.OODSPFormatterData[frameId].requestToken &&
      drupalSettings.OODSPFormatterData[frameId].requestToken.length > 0) {
      oodspPublicConfigs.push(
        Object.assign(
          drupalSettings.OODSPFormatterData[frameId],
          defaultConfig
        )
      );
    } else {
      oodspConfigs.push(
        Object.assign(
          drupalSettings.OODSPFormatterData[frameId],
          defaultConfig
        )
      );
    }
  }

  DocspaceIntegrationSdk.initScript("oodsp-api-js", drupalSettings.OODSP_Settings.url).then(
    function() {
      const countElements = oodspConfigs.length;

      _initFrames(oodspPublicConfigs);

      for ( let i = 0; i < countElements; i++ ) {
        if ( i === 0 ) {
          if (drupalSettings.OODSP_Settings.isAnonymous ||
            !drupalSettings.OODSP_Settings.currentUser 
          ) {
            _showAuthorizationError(oodspConfigs);
            return;
          }

          DocspaceIntegrationSdk.loginByPasswordHash(
            oodspConfigs[ i ].frameId,
            drupalSettings.OODSP_Settings.currentUser,
            function () {
              return Drupal.OODSP_Utils.getPasswordHash()
            },
            function () {
              _initFrames(oodspConfigs);
            },
            function () {
              _showAuthorizationError(oodspConfigs);
            }
          );
        } else {
          DocSpace.SDK.initSystem({
            src: DocSpace.SDK.src,
            frameId: oodspConfigs[ i ].frameId,
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

  const _initFrames = (configs) => {
    for (const config of configs) {
      config.src = DocSpace.SDK.src;

      DocSpace.SDK.initFrame(config);
    }
  };

  const _showAuthorizationError = (configs) => {
    for (const config of configs) {
      if (DocSpace.SDK.frames[config.frameId]) {
        DocSpace.SDK.frames[config.frameId].destroyFrame();
    }

    Drupal.OODSP_Utils.renderError(config.frameId, {
        header: drupalSettings.OODSP_Settings.messages.unauthorizedHeader,
        message: drupalSettings.OODSP_Settings.messages.unauthorizedMessage,
      });
    }
  };

})(Drupal);

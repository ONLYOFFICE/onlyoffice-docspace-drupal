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
  Drupal.OODSP_Utils = Drupal.OODSP_Utils || {};

  Drupal.OODSP_Utils.getPasswordHash = function () {
    var xhr = new XMLHttpRequest();

    xhr.open("POST", drupalSettings.OODSP_Settings.ajaxUrl, false);
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    xhr.send(JSON.stringify({}));

    if (xhr.status === 200) {
      return JSON.parse(xhr.responseText) || null;
    }

    return null;
  }

  Drupal.OODSP_Utils.setPasswordHash = function (hash = null) {
    var xhr = new XMLHttpRequest();

    var body = {};
    
    if (hash) {
      body.hash = hash;
    }

    xhr.open("POST", drupalSettings.OODSP_Settings.ajaxUrl, false);
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    xhr.send(JSON.stringify(body));

    if (xhr.status === 200) {
      return JSON.parse(xhr.responseText) || null;
    }

    return null;
  }

  Drupal.OODSP_Utils.initLoginManager = function (frameId, onSuccessLogin) {
      DocspaceIntegrationSdk.initScript("oodsp-api-js", drupalSettings.OODSP_Settings.url).then(
        function () {
          DocspaceIntegrationSdk.loginByPasswordHash(
            frameId,
            drupalSettings.OODSP_Settings.currentUser,
            function () {
              return Drupal.OODSP_Utils.getPasswordHash()
            },
            onSuccessLogin,
            function () {
              window.location.replace(drupalSettings.OODSP_Settings.loginUrl);
              return;
            }
          )
        }
      ).catch(function() {
        Drupal.OODSP_Utils.renderError(frameId, {message: drupalSettings.OODSP_Settings.messages.docspaceUnavailable});
      });
  }

  Drupal.OODSP_Utils.renderError = function (id, error) {
    const target = document.getElementById(id);

    let errorElement = document.createElement('div');
    errorElement.className="onlyoffice-error";

    errorElement.innerHTML = `
      <div class="onlyoffice-error-body" >
        <div class="onlyoffice-error-table">
          <div>
              <img src="${drupalSettings.OODSP_Settings.images.onlyoffice}" style="width: 100%; padding: 0 10px; box-sizing: border-box;" />
          </div>
          <div style="padding: 16px;">
            <img src="${drupalSettings.OODSP_Settings.images.unavailable}" style="width: 100%"/>
          </div>
          <div class="header-message">${error.header || ""}</div>
          <div class="message">${error.message}</div>
        </div>
      </div>
    `;

    target.innerHTML = "";
    target.appendChild(errorElement);
}

  Drupal.OODSP_Utils.getAbsoluteUrl = function (url) {
    docSpaceUrl = drupalSettings.OODSP_Settings.url.endsWith("/") ? drupalSettings.OODSP_Settings.url.slice(0, -1) : drupalSettings.OODSP_Settings.url;

    if (url.startsWith("http://") || url.startsWith("https://")) {
      var origin = new URL(url).origin;
      url = url.replace(origin, docSpaceUrl);
    } else {
      url = docSpaceUrl + url;
    }

    return url;
  };


})(jQuery, Drupal);

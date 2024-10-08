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
  var $selectButtons = $('.oodsp-select-button');
  var $removeButtons = $('.oodsp-remove-button');
  var $oodspDialog = $('<div id="oodspModalDialog"><div id="oodsp-selector-frame"></div></div>').appendTo('body');
  var modalConfig = {
    frameId: 'oodsp-selector-frame',
    width: "100%",
    height: "100%",
    selectorType: 'roomsOnly',
    locale: drupalSettings.OODSP_Settings.locale,
    theme: "Base"
  }

  $selectButtons.on('click', function (event) {
    event.preventDefault();

    const widgetId = $(event.target).parents('.onlyoffice-docspace-widget').attr('id');
    const mode = event.target.dataset.mode || null;
    const title = event.target.dataset.title || "";
    const dialog = Drupal.dialog($oodspDialog, {
      title: title,
      width: '400px',
      height: '500px'
    });

    const onSelectRoomCallback = (event) => {
      setInputValue(widgetId, 'target-id', event[0].id);
      setInputValue(widgetId, 'type', 'manager');
      setInputValue(widgetId, 'title', event[0].label);
      setInputValue(widgetId, 'image', event[0].icon ?? "");

      const requestTokens = event[0].requestTokens;
      const requestToken = requestTokens ? requestTokens[0].requestToken : null;

      setInputValue(widgetId, 'request-token', requestToken);

      if (requestToken !== null && requestToken !== "") {
        $('div[data-drupal-selector="' + widgetId + '-fields-field-items-type"] .public-index').removeClass('hidden');
      } else {
        $('div[data-drupal-selector="' + widgetId + '-fields-field-items-type"] .public-index').addClass('hidden');
      }

      if (event[0].icon) {
        $('img[data-drupal-selector="' + widgetId + '-fields-field-image"]')
          .attr('src', Drupal.OODSP_Utils.getAbsoluteUrl(event[0].icon))
          .attr('data-default-src', drupalSettings.OODSP_Settings.images['room-icon']);
      } else {
        $('img[data-drupal-selector="' + widgetId + '-fields-field-image"]')
          .attr('src', drupalSettings.OODSP_Settings.images['room-icon'])
          .attr('data-default-src', drupalSettings.OODSP_Settings.images['room-icon']);
      }

      $('div[data-drupal-selector="' + widgetId + '-fields-field-items-type"] .value').text(drupalSettings.OODSP_Settings.labels['room']);
      $('div[data-drupal-selector="' + widgetId + '-fields-field-items-title"] .value').text(event[0].label);

      $('div[data-drupal-selector="' + widgetId + '-fields"]').removeClass('hidden');
      $('div[data-drupal-selector="' + widgetId + '-buttons"]').addClass('hidden');
      dialog.close();
    }

    const onSelectFileCallback = (event) => {
      setInputValue(widgetId, 'target-id', event.id);
      setInputValue(widgetId, 'type', 'editor');
      setInputValue(widgetId, 'title', event.title);
      setInputValue(widgetId, 'image', event.icon ?? "");

      const requestTokens = event.requestTokens;
      const requestToken = requestTokens ? requestTokens[0].requestToken : null;

      setInputValue(widgetId, 'request-token', requestToken);

      if (requestToken !== null && requestToken !== "") {
        $('div[data-drupal-selector="' + widgetId + '-fields-field-items-type"] .public-index').removeClass('hidden');
      } else {
        $('div[data-drupal-selector="' + widgetId + '-fields-field-items-type"] .public-index').addClass('hidden');
      }

      if (event.icon)
        $('img[data-drupal-selector="' + widgetId + '-fields-field-image"]')
          .attr('src', Drupal.OODSP_Utils.getAbsoluteUrl(event.icon))
          .attr('data-default-src', drupalSettings.OODSP_Settings.images['file-icon']);
      else {
        $('img[data-drupal-selector="' + widgetId + '-fields-field-image"]')
          .attr('src', drupalSettings.OODSP_Settings.images['file-icon'])
          .attr('data-default-src', drupalSettings.OODSP_Settings.images['file-icon']);
      }

      $('div[data-drupal-selector="' + widgetId + '-fields-field-items-type"] .value').text(drupalSettings.OODSP_Settings.labels['file']);
      $('div[data-drupal-selector="' + widgetId + '-fields-field-items-title"] .value').text(event.title);

      $('div[data-drupal-selector="' + widgetId + '-fields"]').removeClass('hidden');
      $('div[data-drupal-selector="' + widgetId + '-buttons"]').addClass('hidden');
      dialog.close();
    }

    const onCloseCallback = () => {
      dialog.close();
    }

    $(window).on('editor:dialogsave', () => {
      DocSpace.SDK.frames['oodsp-selector-frame'].destroyFrame();
    });

    modalConfig.mode = mode;
    switch (mode) {
      case 'room-selector':
        modalConfig.events = {
          onSelectCallback: onSelectRoomCallback,
          onCloseCallback: onCloseCallback
        }
        break;

      case 'file-selector':
        modalConfig.events = {
          onSelectCallback: onSelectFileCallback,
          onCloseCallback: onCloseCallback
        }
        break;
    }

    Drupal.OODSP_Utils.initLoginManager(
      'oodsp-selector-frame',
      function() {
        DocSpace.SDK.initFrame(modalConfig);
      }
    );
    dialog.showModal();
  });

  $removeButtons.on('click', function (event) {
    event.preventDefault();

    const widgetId = $(event.target).parents('.onlyoffice-docspace-widget').attr('id');

    setInputValue(widgetId, 'target-id', '');
    setInputValue(widgetId, 'type', '');
    setInputValue(widgetId, 'title', '');
    setInputValue(widgetId, 'image', '');
    setInputValue(widgetId, 'request-token', '');

    $('div[data-drupal-selector="' + widgetId + '-fields"]').addClass('hidden');
    $('div[data-drupal-selector="' + widgetId + '-buttons"]').removeClass('hidden');
  });

  const setInputValue = function (id, name, value) {
    $('input[data-drupal-selector="' + id + '-' + name + '"]').val(value);
  }

})(jQuery, Drupal);

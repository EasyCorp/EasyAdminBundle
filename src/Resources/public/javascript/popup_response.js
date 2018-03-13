/* global opener */
(function () {
    'use strict';
    var response = JSON.parse(document.getElementById('easyadmin-popup-response-script').dataset.popupResponse);
    opener.dismissAdminPopup(window, response.action, response.value, response.label);
})();

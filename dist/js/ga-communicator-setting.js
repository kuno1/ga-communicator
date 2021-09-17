/*! For license information please see ga-communicator-setting.js.LICENSE.txt */
!function(t){var e={};function a(n){if(e[n])return e[n].exports;var r=e[n]={i:n,l:!1,exports:{}};return t[n].call(r.exports,r,r.exports,a),r.l=!0,r.exports}a.m=t,a.c=e,a.d=function(t,e,n){a.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},a.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},a.t=function(t,e){if(1&e&&(t=a(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(a.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var r in t)a.d(n,r,function(e){return t[e]}.bind(null,r));return n},a.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return a.d(e,"a",e),e},a.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},a.p="",a(a.s=0)}([function(t,e,a){t.exports=a(1)},function(t,e,a){"use strict";var n=jQuery,r=wp.i18n.sprintf,o=wp.apiFetch,c=function(t){var e=n(t).attr("data-key");n(t).addClass("loading"),n(t).addClass("loading"),n(t).find('option[class!="ga-setting-choices-default"]').remove(),o({path:i(e)}).then((function(e){var a=n(t).find(".ga-setting-choices"),o=n(t).find('input[type="hidden"]').val();e.forEach((function(t){var e=n(r('<option value="%s">%s(%s)</option>',t.id,t.name,t.id));t.id===o&&e.attr("selected",!0),a.append(e)}))})).catch((function(t){!function(t,e){var a=n(r('<div class="notice notice-error">%s<span class="notice-dismiss"></span></div>',e));n('div[data-key="'.concat(t,'"]')).append(a),setTimeout((function(){a.remove()}),3e3)}(e,t.message)})).finally((function(){n(t).removeClass("loading")}))},i=function(t){var e="ga/v1";switch(t){case"ga-account":e+="/accounts";break;case"ga-property":e+=r("/properties/%s",s("ga-account")||" ");break;case"ga-profile":e+=r("/profiles/%s/%s",s("ga-account")||" ",s("ga-property")||" ")}return e},s=function(t){return function(t){var e=n('code[data-predefined="'.concat(t,'"]'));return e.length?e.text():""}(t)||n('input[name="'.concat(t,'"]')).val()},u=function(t){n(".ga-setting-example").each((function(e,a){t===n(a).attr("data-sample")?n(a).addClass("toggle"):n(a).removeClass("toggle")}));var e="";t.length&&(e=n('pre[data-example="'.concat(t,'"]')).text()),n("#ga-extra").attr("placeholder","e.g.\n"+e)};n((function(){n(".ga-setting-row").each((function(t,e){c(e),n(e).find("select").change((function(){!function(t){var e=n(t).attr("data-key");n(t).find('input[type="hidden"]').val(n(t).find("select").val());var a=0,r=["ga-profile","ga-property"];switch(e){case"ga-account":a=2;break;case"ga-property":a=1}for(var o=0;o<a;o++)c(n('div[data-key="'.concat(r[o],'"]')))}(e)}))}));var t=n("select#ga-tag");t.length&&(u(t.val()),t.change((function(){u(n(this).val())}))),n(".ga-nav-tab").click((function(t){t.preventDefault(),n(".ga-nav-tab-content").css("display","none"),n(".ga-nav-tab").removeClass("nav-tab-active"),n(n(this).attr("href")).css("display","block"),n(this).addClass("nav-tab-active")}))}))}]);
//# sourceMappingURL=ga-communicator-setting.js.map
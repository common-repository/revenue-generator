<script id="revenue-generator-amp-purchase-component" type="text/plain" target="amp-script">
(()=>{"use strict";function t(t,r){return function(t){if(Array.isArray(t))return t}(t)||function(t,e){if("undefined"!=typeof Symbol&&Symbol.iterator in Object(t)){var r=[],n=!0,a=!1,o=void 0;try{for(var i,s=t[Symbol.iterator]();!(n=(i=s.next()).done)&&(r.push(i.value),!e||r.length!==e);n=!0);}catch(t){a=!0,o=t}finally{try{n||null==s.return||s.return()}finally{if(a)throw o}}return r}}(t,r)||e(t,r)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function e(t,e){if(t){if("string"==typeof t)return r(t,e);var n=Object.prototype.toString.call(t).slice(8,-1);return"Object"===n&&t.constructor&&(n=t.constructor.name),"Map"===n||"Set"===n?Array.from(t):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?r(t,e):void 0}}function r(t,e){(null==e||e>t.length)&&(e=t.length);for(var r=0,n=new Array(e);r<e;r++)n[r]=t[r];return n}function n(t,r){var n;if("undefined"==typeof Symbol||null==t[Symbol.iterator]){if(Array.isArray(t)||(n=e(t))||r&&t&&"number"==typeof t.length){n&&(t=n);var a=0,o=function(){};return{s:o,n:function(){return a>=t.length?{done:!0}:{done:!1,value:t[a++]}},e:function(t){throw t},f:o}}throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var i,s=!0,c=!1;return{s:function(){n=t[Symbol.iterator]()},n:function(){var t=n.next();return s=t.done,t},e:function(t){c=!0,i=t},f:function(){try{s||null==n.return||n.return()}finally{if(c)throw i}}}}function a(t,e){if(e&&void 0!==e)if(e instanceof Array)e.map((function(e){return a(t,e)}));else{var r=e;"string"==typeof r&&(r=document.createTextNode(r.toString())),t.appendChild(r)}}function o(t){return t.replace(/([a-z0-9])([A-Z])/g,"$1-$2").toLowerCase()}function i(t,e){if("function"==typeof t.render)return t.render();if(t instanceof Function)return t(e);if(t instanceof HTMLElement)return s(t,e),t;var r=document.createElement(t);return s(r,e),r}function s(n,a){null==a&&(a={});for(var i=0,s=Object.entries(a);i<s.length;i++){var c=t(s[i],2),u=c[0],l=c[1];if(!0===l)n.setAttribute(u,u);else if(u.startsWith("on")&&"function"==typeof l)n.addEventListener(u.substr(2).toLowerCase(),l);else if(!1!==l&&null!=l){var f;l instanceof Object&&function(){var e="style"===u?o:function(t){return t.toLowerCase()};l=Object.entries(l).map((function(r){var n=t(r,2),a=n[0],o=n[1];return"".concat(e(a),": ").concat(o)})).join("; ")}(),"className"===u&&""!==l?(f=n.classList).add.apply(f,function(t){if(Array.isArray(t))return r(t)}(d=l.toString().trim().split(" "))||function(t){if("undefined"!=typeof Symbol&&Symbol.iterator in Object(t))return Array.from(t)}(d)||e(d)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()):n.setAttribute(u,l.toString())}}var d}Object.entries||(Object.entries=function(t){for(var e=Object.keys(t),r=e.length,n=new Array(r);r--;)n[r]=[e[r],t[e[r]]];return n});var c=function(t,e){var r=document.createElementNS("http://www.w3.org/2000/svg","svg");s(r,e);for(var o=arguments.length,i=new Array(o>2?o-2:0),c=2;c<o;c++)i[c-2]=arguments[c];for(var u=0,l=i;u<l.length;u++){var f,d=l[u],b=document.createElementNS("http://www.w3.org/2000/svg",d.nodeName.toLowerCase()),m=n(d.attributes);try{for(m.s();!(f=m.n()).done;){var y=f.value;b.setAttribute(y.name,y.value)}}catch(t){m.e(t)}finally{m.f()}a(r,b)}return r};const u=function(t,e){for(var r=arguments.length,n=new Array(r>2?r-2:0),o=2;o<r;o++)n[o-2]=arguments[o];if("svg"===t)return c.apply(void 0,[t,e].concat(n));for(var s=i(t,e),u=0,l=n;u<l.length;u++){var f=l[u];a(s,f)}return s},l=56*Math.PI,f=function({id:t,strokeDashoffset:e,arcSize:r,className:n,strokeWidth:a}){return u("svg",{id:t,className:n,xmlns:"http://www.w3.org/2000/svg",height:r+a,width:r+a,"stroke-width":a,fill:"none","stroke-linecap":"round"},u("circle",{className:n,cx:32,cy:28,fill:"none",r:28,transform:"rotate(110, 30, 30)","stroke-dasharray":`${l} ${l}`,"stroke-dashoffset":e,"stroke-width":a}))},d=function({tabAmount:t,tabCurrency:e,tabLimit:r,viewTabUrl:n,parentNode:a}){const o=t>0?l-t/r*(l-20):l;a.style.setProperty("--strokeDashOffset",o);const i=t=>Intl.NumberFormat("en-US",{style:"currency",currency:e}).format(t),s=i(t);return u("div",{id:"tab-widget",className:"tab_widget"},u("div",{className:"tab_widget__container tab_widget__container-visible"},u("div",{className:"tab_widget__arc-container"},u(f,{arcSize:60,strokeWidth:5,strokeDashoffset:20,className:"tab_widget__arc--background"}),u(f,{arcSize:60,strokeWidth:5,strokeDashoffset:o,className:"tab_widget__arc--filled tab_widget__arc-animation"}),u("span",{className:"tab_widget__donated-amount-text"},s)),u("div",null,u("p",{className:"tab_widget__thank-you-message"},"Thanks for your support!"),u("p",{className:"tab_widget__regular-text"},"You've used ",u("b",null,s)," of your ",u("b",null,i(r))," Tab."),u("div",{className:"tab_widget__links-container"},u("a",{href:n,className:"tab_widget__link"},"View Tab"),u("span",{className:"tab_widget__links-separator"},"|"),u("a",{role:"button",className:"tab_widget__link"},"About MyTab")))))};document.querySelectorAll(".lp__tab-widget-data").forEach((t=>{const e=t.getAttribute("data-tab-amount"),r=t.getAttribute("data-tab-currency"),n=t.getAttribute("data-tab-limit"),a=t.getAttribute("data-view-tab-url"),o=t.parentNode,i=u(d,{tabAmount:e,tabCurrency:r,tabLimit:n,viewTabUrl:a,parentNode:o});o.replaceChild(i,t)}))})();
</script>

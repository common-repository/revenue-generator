(()=>{"use strict";(()=>{class t{constructor(t){this.el=t,this.$o={donateBox:t.querySelector(".rev-gen-contribution__donate"),customBox:{el:t.querySelector(".rev-gen-contribution__custom"),form:t.querySelector("form"),input:t.querySelector(".rev-gen-contribution-custom input"),backButton:t.querySelector(".rev-gen-contribution-custom__back"),send:t.querySelector(".rev-gen-contribution-custom__send")},amounts:t.getElementsByClassName("rev-gen-contribution__donation"),customAmount:t.querySelector(".rev-gen-contribution__donation--custom"),tip:t.querySelector(".rev-gen-contribution__tip")},this.bindEvents()}bindEvents(){for(const t of this.$o.amounts){const e=t.querySelector("a");e&&(e.addEventListener("mouseover",(()=>{"ppu"===t.dataset.revenue?this.$o.tip.classList.remove("rev-gen-hidden"):this.$o.tip.classList.add("rev-gen-hidden")})),e.addEventListener("mouseout",(()=>{this.$o.tip.classList.add("rev-gen-hidden")})),new ResizeObserver((t=>{t.forEach((t=>{const e=t.target.dataset.breakpoints?JSON.parse(t.target.dataset.breakpoints):"";e&&Object.keys(e).forEach((o=>{const s=e[o],n="size-"+o;t.contentRect.width>=s?t.target.classList.add(n):t.target.classList.remove(n)}))}))})).observe(this.el))}this.$o.customAmount.addEventListener("click",(t=>{t.preventDefault(),this.$o.donateBox.classList.add("rev-gen-hidden"),this.$o.customBox.el.classList.remove("rev-gen-hidden"),this.$o.customBox.el.removeAttribute("hidden"),this.$o.customBox.input.focus()})),this.$o.customBox.backButton.addEventListener("click",(()=>{this.$o.customBox.el.classList.add("rev-gen-hidden"),this.$o.customBox.el.setAttribute("hidden",""),this.$o.donateBox.classList.remove("rev-gen-hidden")})),this.$o.customBox.input.addEventListener("change",(()=>{this.validateAmount(),199>=this.getCustomAmount(!0)?this.$o.tip.classList.remove("rev-gen-hidden"):this.$o.tip.classList.add("rev-gen-hidden")})),this.$o.customBox.input.addEventListener("keyup",(()=>{199>=this.getCustomAmount(!0)?this.$o.tip.classList.remove("rev-gen-hidden"):this.$o.tip.classList.add("rev-gen-hidden")})),this.$o.customBox.form.addEventListener("submit",(t=>{t.preventDefault(),this.$o.customBox.send.classList.add("loading"),this.$o.customBox.send.setAttribute("disabled",!0);const e=this,o=new FormData(this.$o.customBox.form),s=new XMLHttpRequest;s.open("POST",this.$o.customBox.form.getAttribute("action"),!0),s.send(o),s.onreadystatechange=function(){if(4===this.readyState)if(e.$o.customBox.send.classList.remove("loading"),e.$o.customBox.send.removeAttribute("disabled"),200===this.status){const t=JSON.parse(this.response);t.data?(e.$o.customBox.form.classList.remove("error"),window.open(t.data)):e.$o.customBox.form.classList.add("error")}else e.$o.customBox.form.classList.add("error")}}))}validateAmount(){let t=this.$o.customBox.input.value;return t=t.toString().replace(/[^0-9\,\.]/g,""),t="string"==typeof t&&t.indexOf(",")>-1?parseFloat(t.replace(",",".")):parseFloat(t),t=t.toFixed(2),isNaN(t)&&(t=.05),t=Math.abs(t),t>1e3?t=1e3:t<.05&&(t=.05),this.$o.customBox.input.value=t,t}getCustomAmount(t){let e=this.$o.customBox.input.value;return t&&(e*=100),e}}class e{constructor(t){this.$button={trigger:t.querySelector("button"),modal:t.querySelector(".rev-gen-contribution-modal")},this.$modal={el:""},this.bindButtonEvents()}bindButtonEvents(){this.$button.trigger.addEventListener("click",this.open.bind(this))}bindModalEvents(){this.$modal.closeButton.addEventListener("click",this.close.bind(this))}open(t){t.preventDefault();const e=this.$button.modal.cloneNode(!0);this.$modal.el=e,this.$modal.contributionEl=e.querySelector(".rev-gen-contribution"),this.$modal.closeButton=e.querySelector(".rev-gen-contribution-modal__close"),document.querySelector("body").appendChild(e),this.bindModalEvents(),this.initContributionRequest(),setTimeout((function(){e.classList.add("active")}),100)}initContributionRequest(){this.contributionInstance=new t(this.$modal.contributionEl)}close(t){t.preventDefault();const e=this.$modal.el;e.classList.remove("active"),setTimeout((function(){document.querySelector("body").removeChild(e)}),200)}}document.addEventListener("DOMContentLoaded",(()=>{const o=document.getElementsByClassName("rev-gen-contribution");for(const s of o)"button"!==s.dataset.type?new t(s):new e(s)}))})()})();
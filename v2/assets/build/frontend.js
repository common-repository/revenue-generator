(()=>{"use strict";(()=>{const t=t=>{const e=t+"=",o=decodeURIComponent(document.cookie).split(";");for(let t=0;t<o.length;t++){let s=o[t];for(;" "===s.charAt(0);)s=s.substring(1);if(0===s.indexOf(e))return s.substring(e.length,s.length)}return""},e=(t,e,o)=>{const s=new Date;s.setTime(s.getTime()+1e3*o);const i="expires="+s.toUTCString();document.cookie=t+"="+e+";"+i+";path=/"};class o{constructor(t){this.el=t,this.itemId=this.el.dataset.contributionId,this.successEvent=new Event("rg-contribution-success"),this.hiddenClassName="rev-gen-hidden",this.$o={form:t.querySelector(".rev-gen-contribution__form"),submitButton:t.querySelector("button[type=submit][data-mytab-button]"),amounts:t.querySelector(".rev-gen-contribution__amounts"),choose:t.querySelector(".rev-gen-contribution__choose"),response:t.querySelector(".rev-gen-contribution__response"),custom:{el:t.querySelector(".rev-gen-contribution__custom"),input:t.querySelector(".rev-gen-contribution__custom input"),choice:t.querySelector(".rev-gen-contribution__amount--custom"),backButton:t.querySelector(".rev-gen-contribution-custom__back")},modal:{el:t.querySelector(".rev-gen-contribution-info-modal"),openButton:t.querySelector(".rev-gen-contribution__question-mark"),closeButton:t.querySelector(".rev-gen-contribution-info-modal__x-mark")},footer:{el:t.parentElement,toggle:t.querySelector(".rev-gen-footer-contribution .rev-gen-contribution__toggle")}},this.isFooter=this.$o.footer.el.classList.contains("rev-gen-footer-contribution"),this.dismissedFooterCookieName="rg_footer_contribution_dismiss_until",this.setStep("default"),this.bindEvents(),this.maybeContinueFlow(),this.isFooter&&this.initFooter()}bindEvents(){const o=this;new ResizeObserver((t=>{t.forEach((t=>{const e=t.target.dataset.breakpoints?JSON.parse(t.target.dataset.breakpoints):"";e&&Object.keys(e).forEach((o=>{const s=e[o],i="size-"+o;t.contentRect.width>=s?t.target.classList.add(i):t.target.classList.remove(i)}))}))})).observe(this.el),this.$o.custom.choice.addEventListener("click",(t=>{t.preventDefault(),this.setStep("custom"),this.$o.custom.input.focus()})),this.$o.custom.input.addEventListener("keyup",(()=>{const t=parseFloat(this.$o.custom.input.value);!isNaN(t)&&isFinite(t)?this.$o.submitButton.removeAttribute("disabled"):this.$o.submitButton.setAttribute("disabled","disabled")})),this.$o.custom.backButton.addEventListener("click",(t=>{t.preventDefault(),this.setStep("default")})),this.$o.form.addEventListener("change",(()=>{this.customModeActive||(new FormData(this.$o.form).get("amount")?this.setStep("valid"):this.setStep("default"))})),this.$o.form.addEventListener("submit",(s=>{s.preventDefault(),this.setStep("loading");const i=new FormData(this.$o.form);if(i.append("rg_key",t("rg_key")),!i.get("amount")&&!i.get("custom_amount"))return void this.setStep("error");const n=new XMLHttpRequest;n.open("POST",this.$o.form.getAttribute("action"),!0),n.send(i),n.onreadystatechange=function(){if(4===this.readyState){const t=JSON.parse(this.response);switch(this.status){case 200:const s=document.createElement("script");s.src=o.$o.form.dataset.tabWidgetUrl,o.$o.response.appendChild(s),o.$o.response.innerHTML=t.data.html,s.onload=()=>{o.setStep("loaded"),o.el.querySelectorAll(".tab_widget__link")[1].addEventListener("click",(()=>{o.openInfoModal()})),o.el.dispatchEvent(o.successEvent)};break;case 402:o.$o.response.innerHTML=t.data.html;break;case 401:e("rg_key",t.data.session_key),e("rg_contribution_data",JSON.stringify(t.data.handover)),window.location.href=t.data.auth_url}}}})),this.$o.modal.openButton?.addEventListener("click",(()=>{o.openInfoModal()})),this.$o.modal.closeButton?.addEventListener("click",(()=>{o.closeInfoModal()})),this.el.addEventListener("rg-contribution-success",(()=>{if(!this.isFooter)return;const t=parseInt(this.el.dataset.dismissFor,10)||86400;e(this.dismissedFooterCookieName,"yes",t),setTimeout((()=>{o.setStep("default"),o.completelyHideFooter()}),5e3)})),window.addEventListener("message",(t=>{t.data&&"OPEN_INFO_MODAL"===t.data&&o.openInfoModal(),t.data&&"PAYMENT_SUCCESSFUL"===t.data&&o.el.dispatchEvent(o.successEvent)})),this.el.addEventListener("rev-gen-iframe-load",(t=>{const e=t.detail||"";e&&(this.setStep("loaded"),e.style.height=e.contentWindow.document.body.scrollHeight+"px")})),this.$o.footer.toggle?.addEventListener("click",(t=>{t.preventDefault(),this.$o.footer.el.classList.toggle("rev-gen-footer-contribution--collapsed")}))}openInfoModal(){clearTimeout(this.closeTimeout),this.$o.modal.el.classList.remove(this.hiddenClassName)}closeInfoModal(){this.$o.modal.el.classList.add(this.hiddenClassName)}initFooter(){t(this.dismissedFooterCookieName).length||this.$o.footer.el.classList.add("rev-gen-footer-contribution--active")}completelyHideFooter(){this.$o.footer.el.classList.remove("rev-gen-footer-contribution--active")}setStep(t){if(this.step=t,this.el.dataset.step=t,this.customModeActive=!1,"custom"===t){this.customModeActive=!0,this.$o.custom.input.value||this.$o.submitButton.setAttribute("disabled","disabled");const t=this.$o.amounts.querySelectorAll("input"),e=[].filter.call(t,(t=>t.checked));e.length&&(e[0].checked=!1)}else"default"===t&&(this.$o.submitButton.removeAttribute("disabled"),this.$o.custom.input.value="")}reset(){this.$o.response.innerHTML="",this.el.classList.remove("rev-gen-contribution--payment"),this.$o.form.reset(),this.$o.choose.classList.remove(this.hiddenClassName)}maybeContinueFlow(){if(!t("rg_contribution_data"))return;const o=JSON.parse(t("rg_contribution_data"));if(parseInt(this.itemId,10)!==parseInt(o.contribution.item_id,10))return;const s=this.$o.form.querySelector('[value="'+o.contribution.amount+'"]');window.scrollTo({top:this.el.offsetTop,behavior:"smooth"}),s?s.checked=!0:(this.$o.custom.input.value=parseFloat(o.contribution.amount),this.setStep("custom"));const i=new MouseEvent("click");this.$o.submitButton.dispatchEvent(i),e("rg_contribution_data","")}}window.laterpayIframeLoaded=t=>{const e=t.dataset.contributionId;if(!e)return;const o=new CustomEvent("rev-gen-iframe-load",{detail:t});let s=document.querySelectorAll('.rev-gen-contribution[data-contribution-id="'+e+'"]');s=s[s.length-1],s.dispatchEvent(o)};class s{constructor(t){this.closeTimeout="",this.$button={trigger:t.querySelector("button"),modal:t.querySelector(".rev-gen-contribution-modal")},this.$modal={el:""},this.bindButtonEvents()}bindButtonEvents(){this.$button.trigger.addEventListener("click",this.open.bind(this))}bindModalEvents(){const t=this;this.$modal.closeButton.addEventListener("click",this.closeButtonClick.bind(this)),this.$modal.contributionEl.addEventListener("rg-contribution-success",(()=>{t.closeTimeout=setTimeout((()=>{t.close()}),5e3)})),this.$modal.contributionEl.addEventListener("click",(()=>{clearTimeout(t.closeTimeout)}))}closeButtonClick(t){t.preventDefault(),this.close()}open(t){t.preventDefault();const e=this.$button.modal.cloneNode(!0);this.$modal.el=e,this.$modal.contributionEl=e.querySelector(".rev-gen-contribution"),this.$modal.closeButton=e.querySelector(".rev-gen-contribution-modal__close"),document.querySelector("body").appendChild(e),this.bindModalEvents(),this.initContributionRequest(),setTimeout((function(){e.classList.add("active")}),100)}initContributionRequest(){this.contributionInstance=new o(this.$modal.contributionEl)}close(){const t=this.$modal.el;t.classList.remove("active"),setTimeout((function(){t&&t.remove()}),200)}}document.addEventListener("DOMContentLoaded",(()=>{const t=document.getElementsByClassName("rev-gen-contribution");for(const e of t)e.dataset.type&&("button"!==e.dataset.type?new o(e):new s(e))}))})()})();
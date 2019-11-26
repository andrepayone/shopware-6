(this.webpackJsonp=this.webpackJsonp||[]).push([["payone-payment"],{"5ZwK":function(e){e.exports=JSON.parse('{"payone-payment":{"title":"PAYONE","general":{"mainMenuItemGeneral":"PAYONE","descriptionTextModule":"Einstellungen für PAYONE"},"capture":{"buttonTitle":"Capture","successTitle":"PAYONE","successMessage":"Capture erfolgreich durchgeführt.","errorTitle":"PAYONE"},"refund":{"buttonTitle":"Refund","successTitle":"PAYONE","successMessage":"Refund erfolgreich durchgeführt.","errorTitle":"PAYONE"},"settingsForm":{"save":"Speichern","test":"API-Zugangsdaten testen","titleSuccess":"Erfolg","titleError":"Fehler","messageTestSuccess":"Die API-Zugangsdaten wurden erfolgreich validiert.","messageTestError":{"general":"Die API-Zugangsdaten konnten nicht validiert werden.","creditCard":"Die API-Zugangsdaten für Kreditkarte sind nicht korrekt.","debit":"Die API-Zugangsdaten für Lastschrift sind nicht korrekt.","paypalExpress":"Die API-Zugangsdaten für PayPal Express sind nicht korrekt.","paypal":"Die API-Zugangsdaten für PayPal sind nicht korrekt.","payolutionInstallment":"Die API-Zugangsdaten für Payolution Installment sind nicht korrekt.","payolutionInvoicing":"Die API-Zugangsdaten für Payolution Invoicing sind nicht korrekt.","sofort":"Die API-Zugangsdaten für SOFORT sind nicht korrekt."}},"messageNotBlank":"Dieser Wert darf nicht leer sein.","txid":"TXID","sequenceNumber":{"label":"Sequenznummer","empty":"keine"},"transactionState":"Status"}}')},"6yeL":function(e,t,n){},"8ajy":function(e,t){e.exports='{% block sw_settings_content_card_slot_plugins %}\n    {% parent %}\n\n    <sw-settings-item :label="$tc(\'payone-payment.general.mainMenuItemGeneral\')"\n                      :to="{ name: \'payone.payment.index\' }"\n                      :backgroundEnabled="false">\n        <template #icon>\n            \x3c!-- TODO: Image only works in production mode --\x3e\n            <img class="sw-settings-index__payone-icon" :src="\'payonepayment/plugin.png\' | asset">\n        </template>\n    </sw-settings-item>\n{% endblock %}\n'},DisY:function(e,t,n){"use strict";n.r(t);var s=n("HTar"),a=n.n(s);const{Component:i,Mixin:o}=Shopware,{Criteria:r}=Shopware.Data;i.register("payone-settings",{template:a.a,mixins:[o.getByName("notification"),o.getByName("sw-inline-snippet")],inject:["PayonePaymentApiCredentialsService"],data:()=>({isLoading:!1,isTesting:!1,isSaveSuccessful:!1,isTestSuccessful:!1,config:{},merchantIdFilled:!1,accountIdFilled:!1,portalIdFilled:!1,portalKeyFilled:!1,showValidationErrors:!1}),computed:{credentialsMissing:function(){return!(this.merchantIdFilled&&this.accountIdFilled&&this.portalIdFilled&&this.portalKeyFilled)}},metaInfo(){return{title:this.$createTitle()}},methods:{paymentMethodPrefixes:()=>["creditCard","debit","paypal","paypalExpress","payolutionInvoicing","payolutionInstallment","sofort"],saveFinish(){this.isSaveSuccessful=!1},testFinish(){this.isTestSuccessful=!1},onConfigChange(e){this.config=e,this.checkCredentialsFilled(),this.showValidationErrors=!1},checkCredentialsFilled(){this.merchantIdFilled=!!this.getConfigValue("merchantId"),this.accountIdFilled=!!this.getConfigValue("accountId"),this.portalIdFilled=!!this.getConfigValue("portalId"),this.portalKeyFilled=!!this.getConfigValue("portalKey")},getConfigValue(e){const t=this.$refs.systemConfig.actualConfigData.null;return null===this.$refs.systemConfig.currentSalesChannelId?this.config[`PayonePayment.settings.${e}`]:this.config[`PayonePayment.settings.${e}`]||t[`PayonePayment.settings.${e}`]},getPaymentConfigValue(e,t){let n=e.charAt(0).toUpperCase()+e.slice(1);return this.getConfigValue(t+n)||this.getConfigValue(e)},onSave(){this.credentialsMissing?this.showValidationErrors=!0:(this.isSaveSuccessful=!1,this.isLoading=!0,this.$refs.systemConfig.saveAll().then(()=>{this.isLoading=!1,this.isSaveSuccessful=!0}).catch(()=>{this.isLoading=!1}))},onTest(){this.isTesting=!0,this.isTestSuccessful=!1;let e={};this.paymentMethodPrefixes().forEach(t=>{e[t]={merchantId:this.getPaymentConfigValue("merchantId",t),accountId:this.getPaymentConfigValue("accountId",t),portalId:this.getPaymentConfigValue("portalId",t),portalKey:this.getPaymentConfigValue("portalKey",t)}}),this.PayonePaymentApiCredentialsService.validateApiCredentials(e).then(e=>{const t=e.credentialsValid,n=e.errors;if(t)this.createNotificationSuccess({title:this.$tc("payone-payment.settingsForm.titleSuccess"),message:this.$tc("payone-payment.settingsForm.messageTestSuccess")}),this.isTestSuccessful=!0;else for(let e in n)n.hasOwnProperty(e)&&this.createNotificationError({title:this.$tc("payone-payment.settingsForm.titleError"),message:this.$tc("payone-payment.settingsForm.messageTestError."+e)});this.isTesting=!1}).catch(e=>{this.createNotificationError({title:this.$tc("payone-payment.settingsForm.titleError"),message:this.$tc("payone-payment.settingsForm.messageTestError.general")}),this.isTesting=!1})},getBind(e,t){return t!==this.config&&this.onConfigChange(t),this.showValidationErrors&&("PayonePayment.settings.merchantId"!==e.name||this.merchantIdFilled||(e.config.error={code:1,detail:this.$tc("payone-payment.messageNotBlank")}),"PayonePayment.settings.accountId"!==e.name||this.accountIdFilled||(e.config.error={code:1,detail:this.$tc("payone-payment.messageNotBlank")}),"PayonePayment.settings.portalId"!==e.name||this.portalIdFilled||(e.config.error={code:1,detail:this.$tc("payone-payment.messageNotBlank")}),"PayonePayment.settings.portalKey"!==e.name||this.portalKeyFilled||(e.config.error={code:1,detail:this.$tc("payone-payment.messageNotBlank")})),e},getPaymentStatusCriteria(){const e=new r(1,100);return e.addFilter(r.equals("stateMachine.technicalName","order_transaction.state")),e}}});var c=n("jB58"),l=n.n(c);n("FMBs");const{Component:d,Mixin:p}=Shopware;d.override("sw-order-detail-base",{template:l.a,inject:["PayonePaymentService"],mixins:[p.getByName("notification")],data:()=>({disableButtons:!1}),methods:{isPayonePayment:e=>!!e.customFields&&e.customFields.payone_transaction_id,isCapturePossible(e){return!!e.customFields&&(!this.disableButtons&&e.customFields.payone_allow_capture)},isRefundPossible(e){return!!e.customFields&&(!this.disableButtons&&e.customFields.payone_allow_refund)},hasPayonePayment(e){let t=this,n=!1;return!!e.transactions&&(e.transactions.map((function(e){t.isPayonePayment(e)&&(n=!0)})),n)},captureOrder(e){let t=this;this.isPayonePayment(e)&&(t.disableButtons=!0,this.PayonePaymentService.capturePayment(e.id).then(()=>{this.createNotificationSuccess({title:this.$tc("payone-payment.capture.successTitle"),message:this.$tc("payone-payment.capture.successMessage")}),t.reloadEntityData(),t.disableButtons=!1}).catch(e=>{this.createNotificationError({title:this.$tc("payone-payment.capture.errorTitle"),message:e.response.data.message}),t.disableButtons=!1}))},refundOrder(e){let t=this;this.isPayonePayment(e)&&(t.disableButtons=!0,this.PayonePaymentService.refundPayment(e.id).then(()=>{this.createNotificationSuccess({title:this.$tc("payone-payment.refund.successTitle"),message:this.$tc("payone-payment.refund.successMessage")}),t.reloadEntityData(),t.disableButtons=!1}).catch(e=>{this.createNotificationError({title:this.$tc("payone-payment.refund.errorTitle"),message:e.response.data.message}),t.disableButtons=!1}))}}});var m=n("8ajy"),u=n.n(m);n("x2aq");const{Component:y}=Shopware;y.override("sw-settings-index",{template:u.a});var g=n("y60C"),h=n.n(g);const{Component:f}=Shopware;f.override("sw-plugin-list",{template:h.a});var P=n("5ZwK"),w=n("tf04");const{Module:b}=Shopware;b.register("payone-payment",{type:"plugin",name:"PayonePayment",title:"payone-payment.general.mainMenuItemGeneral",description:"payone-payment.general.descriptionTextModule",version:"1.0.0",targetVersion:"1.0.0",icon:"default-action-settings",snippets:{"de-DE":P,"en-GB":w},routeMiddleware(e,t){e(t)},routes:{index:{component:"payone-settings",path:"index",meta:{parentPath:"sw.settings.index"}}}});n("nWwx"),n("xg3G")},FMBs:function(e,t,n){var s=n("6yeL");"string"==typeof s&&(s=[[e.i,s,""]]),s.locals&&(e.exports=s.locals);(0,n("SZ7m").default)("4550fb24",s,!0,{})},HTar:function(e,t){e.exports='{% block payone_payment %}\n    <sw-page class="payone-payment">\n        {% block payone_payment_header %}\n            <template #smart-bar-header>\n                <h2>\n                    {{ $tc(\'sw-settings.index.title\') }}\n                    <sw-icon name="small-arrow-medium-right" small></sw-icon>\n                    {{ $tc(\'payone-payment.title\') }}\n                </h2>\n            </template>\n        {% endblock %}\n\n        {% block payone_payment_actions %}\n            <template #smart-bar-actions>\n                {% block payone_payment_settings_actions_test %}\n                    <sw-button-process @click="onTest"\n                               :isLoading="isTesting"\n                               :processSuccess="isTestSuccessful"\n                               :disabled="credentialsMissing || isLoading">\n                        {{ $tc(\'payone-payment.settingsForm.test\') }}\n                    </sw-button-process>\n                {% endblock %}\n\n                {% block payone_payment_settings_actions_save %}\n                    <sw-button-process\n                        class="sw-settings-login-registration__save-action"\n                        :isLoading="isLoading"\n                        :processSuccess="isSaveSuccessful"\n                        :disabled="isLoading || isTesting"\n                        variant="primary"\n                        @process-finish="saveFinish"\n                        @click="onSave">\n                        {{ $tc(\'payone-payment.settingsForm.save\') }}\n                    </sw-button-process>\n                {% endblock %}\n            </template>\n        {% endblock %}\n\n        {% block payone_payment_settings_content %}\n            <template #content>\n                <sw-card-view>\n                    <sw-system-config\n                            ref="systemConfig"\n                            salesChannelSwitchable\n                            inherit\n                            @config-changed="onConfigChange"\n                            domain="PayonePayment.settings">\n                        <template #card-element="{ element, config }">\n                            <sw-form-field-renderer\n                                :config="{\n                                    componentName: \'sw-entity-single-select\',\n                                    label: getInlineSnippet(element.config.label),\n                                    entity: \'state_machine_state\',\n                                    criteria: getPaymentStatusCriteria(),\n                                }"\n                                v-bind="getBind(element, config)"\n                                v-model="config[element.name]"\n                                v-if="element.name.startsWith(\'PayonePayment.settings.paymentStatus\')">\n                            </sw-form-field-renderer>\n                            <sw-form-field-renderer v-bind="getBind(element, config)"\n                                                    v-model="config[element.name]"\n                                                    v-else>\n                            </sw-form-field-renderer>\n                        </template>\n                    </sw-system-config>\n                </sw-card-view>\n            </template>\n        {% endblock %}\n    </sw-page>\n{% endblock %}\n'},jB58:function(e,t){e.exports='{% block sw_order_detail_delivery_metadata %}\n    {% parent %}\n\n    <template v-if="!isLoading" :isLoading="isLoading">\n        <template v-if="hasPayonePayment(order)">\n            <sw-card class="sw-order-payone-card" :title="$tc(\'payone-payment.title\')">\n                <template v-for="transaction in order.transactions">\n                    <template v-if="isPayonePayment(transaction)">\n                        <sw-container columns="1fr 1fr">\n                            <sw-container>\n                                <sw-description-list>\n                                    <dt>{{ $tc(\'payone-payment.txid\') }}</dt>\n                                    <dd class="sw-order-base__label-sales-channel">{{ transaction.customFields.payone_transaction_id }}</dd>\n\n                                    <dt>{{ $tc(\'payone-payment.sequenceNumber.label\') }}</dt>\n                                    <dd class="sw-order-base__label-sales-channel">\n                                        <span v-if="transaction.customFields.payone_sequence_number == -1">\n                                            {{ $tc(\'payone-payment.sequenceNumber.empty\') }}\n                                        </span>\n                                        <span v-else>\n                                            {{ transaction.customFields.payone_sequence_number }}\n                                        </span>\n                                    </dd>\n\n                                    <dt>{{ $tc(\'payone-payment.transactionState\') }}</dt>\n                                    <dd class="sw-order-base__label-sales-channel">{{ transaction.customFields.payone_transaction_state }}</dd>\n                                </sw-description-list>\n                            </sw-container>\n\n                            <sw-container gap="30px">\n                                <sw-button @click="captureOrder(transaction)" :disabled="!isCapturePossible(transaction)">\n                                    {{ $tc(\'payone-payment.capture.buttonTitle\') }}\n                                </sw-button>\n\n                                <sw-button @click="refundOrder(transaction)" :disabled="!isRefundPossible(transaction)">\n                                    {{ $tc(\'payone-payment.refund.buttonTitle\') }}\n                                </sw-button>\n                            </sw-container>\n                        </sw-container>\n                    </template>\n                </template>\n            </sw-card>\n        </template>\n    </template>\n{% endblock %}\n'},nWwx:function(e,t){const{Application:n}=Shopware,s=Shopware.Classes.ApiService;class a extends s{constructor(e,t,n="payone"){super(e,t,n)}capturePayment(e){const t=`_action/${this.getApiBasePath()}/capture-payment`;return this.httpClient.post(t,{transaction:e},{headers:this.getBasicHeaders()}).then(e=>s.handleResponse(e))}refundPayment(e){const t=`_action/${this.getApiBasePath()}/refund-payment`;return this.httpClient.post(t,{transaction:e},{headers:this.getBasicHeaders()}).then(e=>s.handleResponse(e))}}n.addServiceProvider("PayonePaymentService",e=>{const t=n.getContainer("init");return new a(t.httpClient,e.loginService)})},tf04:function(e){e.exports=JSON.parse('{"payone-payment":{"title":"PAYONE","general":{"mainMenuItemGeneral":"PAYONE","descriptionTextModule":"Settings for PAYONE"},"capture":{"buttonTitle":"Capture","successTitle":"PAYONE","successMessage":"Capture processed successfully.","errorTitle":"PAYONE"},"refund":{"buttonTitle":"Refund","successTitle":"PAYONE","successMessage":"Refund processed successfully.","errorTitle":"PAYONE"},"settingsForm":{"save":"Save","test":"Test API Credentials","titleSuccess":"Success","titleError":"Error","messageTestSuccess":"The API credentials were verified successfully.","messageTestError":{"general":"The API credentials could not be verified successfully.","creditCard":"The API credentials for Credit Card are not valid.","debit":"The API credentials for Debit are not valid.","paypalExpress":"The API credentials for PayPal Express are not valid.","paypal":"The API credentials for PayPal are not valid.","payolutionInstallment":"The API credentials for Payolution Installment are not valid.","payolutionInvoicing":"The API credentials for Payolution Invoicing are not valid.","sofort":"The API credentials for SOFORT are not valid."}},"messageNotBlank":"This field must not be empty.","txid":"TXID","sequenceNumber":{"label":"Sequence Number","empty":"none"},"transactionState":"State"}}')},tuq3:function(e,t,n){},x2aq:function(e,t,n){var s=n("tuq3");"string"==typeof s&&(s=[[e.i,s,""]]),s.locals&&(e.exports=s.locals);(0,n("SZ7m").default)("defda1f4",s,!0,{})},xg3G:function(e,t){const{Application:n}=Shopware,s=Shopware.Classes.ApiService;class a extends s{constructor(e,t,n="payone_payment"){super(e,t,n)}validateApiCredentials(e){const t=this.getBasicHeaders();return this.httpClient.post(`_action/${this.getApiBasePath()}/validate-api-credentials`,{credentials:e},{headers:t}).then(e=>s.handleResponse(e))}}n.addServiceProvider("PayonePaymentApiCredentialsService",e=>{const t=n.getContainer("init");return new a(t.httpClient,e.loginService)})},y60C:function(e,t){e.exports="{% block sw_plugin_list_grid_columns_actions_settings %}\n    <template v-if=\"item.composerName === 'payone/shopware'\">\n        <sw-context-menu-item :routerLink=\"{ name: 'payone.payment.index' }\">\n            {{ $tc('sw-plugin.list.config') }}\n        </sw-context-menu-item>\n    </template>\n\n    <template v-else>\n        {% parent %}\n    </template>\n{% endblock %}\n"}},[["DisY","runtime","vendors-node"]]]);
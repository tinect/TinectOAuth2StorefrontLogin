(()=>{var i=`{% block tinect_oauth_storefront_client_listing_page %}
<sw-page class="tinect-oauth-storefront-client-listing-page">
    {% block tinect_oauth_storefront_client_listing_smart_bar_header %}
    <template #smart-bar-header>
        <h2>
            {{ $tc('tinect-oauth-storefront-client.list.title') }}
        </h2>
    </template>
    {% endblock %}

    {% block tinect_oauth_storefront_client_listing_smart_bar_actions %}
    <template #smart-bar-actions>
        <sw-button
            variant="primary"
            @click="onCreateNew"
        >
            {{ $tc('tinect-oauth-storefront-client.list.buttonCreate') }}
        </sw-button>
    </template>
    {% endblock %}

    {% block tinect_oauth_storefront_client_listing_content %}
    <template #content>
        <sw-entity-listing
            v-if="clients"
            ref="swEntityListing"
            :items="clients"
            :columns="columns"
            :repository="repository"
            :is-loading="isLoading"
            :allow-inline-edit="false"
            detail-route="tinect.oauth.storefront.client.edit"
        >
            {% block tinect_oauth_storefront_client_listing_column_active %}
            <template #column-active="{ item }">
                <sw-icon
                    v-if="item.active"
                    name="regular-checkmark-circle"
                    color="#37D046"
                ></sw-icon>
                <sw-icon
                    v-else
                    name="regular-times-circle"
                    color="#DE294C"
                ></sw-icon>
            </template>
            {% endblock %}

            {% block tinect_oauth_storefront_client_listing_column_provider %}
            <template #column-provider="{ item }">
                {{ $tc('tinect-oauth-storefront-client.provider.' + item.provider) }}
            </template>
            {% endblock %}
        </sw-entity-listing>
        <sw-empty-state
            v-else-if="!isLoading"
            :title="$tc('tinect-oauth-storefront-client.list.emptyState')"
        ></sw-empty-state>
    </template>
    {% endblock %}
</sw-page>
{% endblock %}`;var{Component:d,Mixin:p}=Shopware,{Criteria:o}=Shopware.Data;d.register("tinect-oauth-storefront-client-listing-page",{template:i,inject:["repositoryFactory"],mixins:[p.getByName("listing")],data(){return{repository:null,clients:null,isLoading:!0,sortBy:"name",sortDirection:"ASC"}},metaInfo(){return{title:this.$createTitle()}},computed:{columns(){return[{property:"name",label:this.$tc("tinect-oauth-storefront-client.list.columnName"),routerLink:"tinect.oauth.storefront.client.edit",primary:!0,allowResize:!0},{property:"provider",label:this.$tc("tinect-oauth-storefront-client.list.columnProvider"),allowResize:!0},{property:"active",label:this.$tc("tinect-oauth-storefront-client.list.columnActive"),allowResize:!0}]}},created(){this.repository=this.repositoryFactory.create("tinect_oauth_storefront_client"),this.getList()},methods:{getList(){this.isLoading=!0;let t=new o(this.page,this.limit);t.addSorting(o.sort(this.sortBy,this.sortDirection)),this.term&&t.setTerm(this.term),this.repository.search(t,Shopware.Context.api).then(e=>{this.clients=e,this.total=e.total,this.isLoading=!1})},onCreateNew(){this.$router.push({name:"tinect.oauth.storefront.client.create"})}}});var n=`{% block tinect_oauth_storefront_client_edit_page %}
<sw-page class="tinect-oauth-storefront-client-edit-page">
    {% block tinect_oauth_storefront_client_edit_smart_bar_header %}
    <template #smart-bar-header>
        <h2>
            <template v-if="isCreateMode">
                {{ $tc('tinect-oauth-storefront-client.create.title') }}
            </template>

            <template v-else>
                {{ $tc('tinect-oauth-storefront-client.edit.title') }}
                -
                {{ client?.name }}
            </template>
        </h2>
    </template>
    {% endblock %}

    {% block tinect_oauth_storefront_client_edit_smart_bar_actions %}
    <template #smart-bar-actions>
        <sw-button
            :disabled="isLoading"
            @click="onBack"
        >
            {{ $tc('tinect-oauth-storefront-client.edit.buttonBack') }}
        </sw-button>
        <sw-button-process
            v-if="!isCreateMode"
            class="sw-settings-login-registration__save-action"
            :is-loading="isLoading"
            :process-success="isSaveSuccessful"
            variant="primary"
            @click="onSave"
        >
            {{ $tc('tinect-oauth-storefront-client.edit.buttonSave') }}
        </sw-button-process>
        <sw-button
            v-else
            variant="primary"
            :is-loading="isLoading"
            :disabled="!client || !client.name || !client.provider"
            @click="onSave"
        >
            {{ $tc('tinect-oauth-storefront-client.create.buttonSave') }}
        </sw-button>
    </template>
    {% endblock %}

    {% block tinect_oauth_storefront_client_edit_content %}
    <template #content>
        <sw-card-view v-if="client">
            {% block tinect_oauth_storefront_client_edit_card_base %}
            <sw-card
                :title="$tc('tinect-oauth-storefront-client.edit.cardTitleBase')"
                :is-loading="isLoading"
            >
                <sw-container
                    columns="repeat(auto-fit, minmax(250px, 1fr))"
                    gap="0px 30px"
                >
                    <sw-text-field
                        v-model:value="client.name"
                        :label="$tc('tinect-oauth-storefront-client.edit.labelName')"
                        :placeholder="$tc('tinect-oauth-storefront-client.edit.labelName')"
                        required
                    ></sw-text-field>
                    <sw-single-select
                        v-model:value="client.provider"
                        :options="providerOptions"
                        :label="$tc('tinect-oauth-storefront-client.create.labelProvider')"
                        :placeholder="$tc('tinect-oauth-storefront-client.create.placeholderProvider')"
                        required
                    ></sw-single-select>
                    <sw-switch-field
                        v-model:value="client.active"
                        :label="$tc('tinect-oauth-storefront-client.edit.labelActive')"
                    ></sw-switch-field>
                    <sw-switch-field
                        v-model:value="client.connectOnly"
                        :label="$tc('tinect-oauth-storefront-client.edit.labelConnectOnly')"
                        :help-text="$tc('tinect-oauth-storefront-client.edit.labelConnectOnlyHelp')"
                    ></sw-switch-field>
                    <sw-switch-field
                        v-model:value="client.trustEmail"
                        :label="$tc('tinect-oauth-storefront-client.edit.labelTrustEmail')"
                        :help-text="$tc('tinect-oauth-storefront-client.edit.labelTrustEmailHelp')"
                    ></sw-switch-field>
                    <sw-switch-field
                        v-model:value="client.updateEmailOnLogin"
                        :disabled="client.trustEmail"
                        :label="$tc('tinect-oauth-storefront-client.edit.labelUpdateEmailOnLogin')"
                        :help-text="$tc('tinect-oauth-storefront-client.edit.labelUpdateEmailOnLoginHelp')"
                    ></sw-switch-field>
                </sw-container>
            </sw-card>
            {% endblock %}

            {% block tinect_oauth_storefront_client_edit_card_provider %}
            <sw-card
                v-if="providerSettingsComponent"
                :title="$tc('tinect-oauth-storefront-client.edit.cardTitleProviderSettings')"
                :is-loading="isLoading"
            >
                <component
                    :is="providerSettingsComponent"
                    :item="client"
                ></component>
            </sw-card>
            {% endblock %}

            {% block tinect_oauth_storefront_client_edit_card_danger %}
            <sw-card
                v-if="!isCreateMode"
                title="Danger Zone"
            >
                <sw-button
                    variant="danger"
                    @click="showDeleteModal = true"
                    :is-loading="isLoading"
                >
                    {{ $tc('tinect-oauth-storefront-client.edit.buttonDelete') }}
                </sw-button>
                <sw-confirm-modal
                    v-if="showDeleteModal"
                    type="delete"
                    :text="$tc('tinect-oauth-storefront-client.edit.deleteConfirmText')"
                    @confirm="onDelete"
                    @cancel="showDeleteModal = false"
                    @close="showDeleteModal = false"
                ></sw-confirm-modal>
            </sw-card>
            {% endblock %}
        </sw-card-view>
    </template>
    {% endblock %}
</sw-page>
{% endblock %}`;var{Component:h,Mixin:m}=Shopware;h.register("tinect-oauth-storefront-client-edit-page",{template:n,inject:["repositoryFactory"],mixins:[m.getByName("notification")],props:{clientId:{type:String,required:!1,default:null}},data(){return{client:null,isLoading:!1,isSaveSuccessful:!1,showDeleteModal:!1,providerOptions:[{value:"github",label:this.$tc("tinect-oauth-storefront-client.provider.github")},{value:"open_id_connect",label:this.$tc("tinect-oauth-storefront-client.provider.open_id_connect")},{value:"microsoft_entra",label:this.$tc("tinect-oauth-storefront-client.provider.microsoft_entra")},{value:"google_mail",label:this.$tc("tinect-oauth-storefront-client.provider.google_mail")}]}},metaInfo(){return{title:this.$createTitle()}},computed:{isCreateMode(){return!this.clientId},repository(){return this.repositoryFactory.create("tinect_oauth_storefront_client")},providerSettingsComponent(){return!this.client||!this.client.provider?null:`tinect-oauth-provider-${this.client.provider.replace(/_/g,"-")}-settings`}},watch:{clientId(t){t&&this.loadClient()},"client.trustEmail"(t){t&&this.client&&(this.client.updateEmailOnLogin=!1)}},created(){this.isCreateMode?(this.client=this.repository.create(Shopware.Context.api),this.client.active=!1,this.client.connectOnly=!1,this.client.trustEmail=!1,this.client.updateEmailOnLogin=!1,this.client.config={}):this.loadClient()},methods:{loadClient(){this.isLoading=!0,this.repository.get(this.clientId,Shopware.Context.api).then(t=>{this.client=t,this.client.config||(this.client.config={}),this.isLoading=!1})},onSave(){this.isCreateMode&&(!this.client.name||!this.client.provider)||(this.isLoading=!0,this.isSaveSuccessful=!1,this.repository.save(this.client,Shopware.Context.api).then(()=>{if(this.isCreateMode){this.isLoading=!1,this.$router.push({name:"tinect.oauth.storefront.client.edit",params:{id:this.client.id}});return}this.isLoading=!1,this.isSaveSuccessful=!0,this.createNotificationSuccess({message:this.$tc("tinect-oauth-storefront-client.notification.saveSuccess")})}).catch(()=>{this.isLoading=!1,this.createNotificationError({message:this.$tc("tinect-oauth-storefront-client.notification.saveError")})}))},onDelete(){this.showDeleteModal=!1,this.isLoading=!0,this.repository.delete(this.clientId,Shopware.Context.api).then(()=>{this.$router.push({name:"tinect.oauth.storefront.client.list"})}).catch(()=>{this.isLoading=!1,this.createNotificationError({message:this.$tc("tinect-oauth-storefront-client.notification.deleteError")})})},onBack(){this.$router.push({name:"tinect.oauth.storefront.client.list"})}}});var{Module:f}=Shopware;f.register("tinect-oauth-storefront-client",{type:"plugin",name:"tinect-oauth-storefront-client.title",title:"tinect-oauth-storefront-client.title",description:"tinect-oauth-storefront-client.title",color:"#ff68b4",icon:"regular-plug",routes:{list:{component:"tinect-oauth-storefront-client-listing-page",path:"list"},create:{component:"tinect-oauth-storefront-client-edit-page",path:"create"},edit:{component:"tinect-oauth-storefront-client-edit-page",path:"edit/:id",props:{default(t){return{clientId:t.params.id}}}}},settingsItem:[{name:"tinect-oauth-storefront-client",to:"tinect.oauth.storefront.client.list",label:"tinect-oauth-storefront-client.title",group:"plugins",icon:"regular-plug"}]});var l=`{% block tinect_oauth_provider_github_settings %}
<div class="tinect-oauth-provider-github-settings">
    <sw-container
        columns="repeat(auto-fit, minmax(250px, 1fr))"
        gap="0px 30px"
    >
        <sw-text-field
            v-model:value="item.config.clientId"
            label="Client ID"
            placeholder="GitHub OAuth App Client ID"
            required
        ></sw-text-field>
        <sw-password-field
            v-model:value="item.config.clientSecret"
            label="Client Secret"
            placeholder="GitHub OAuth App Client Secret"
            required
        ></sw-password-field>
    </sw-container>
    <sw-container
        columns="1fr"
        gap="0px 30px"
        style="margin-top: 16px;"
    >
        <sw-text-field-deprecated
            :value="callbackUrl"
            label="Authorization callback URL"
            :disabled="true"
            :copyable="true"
            :copyable-tooltip="true"
        ></sw-text-field-deprecated>
    </sw-container>
</div>
{% endblock %}`;var{Component:w}=Shopware;w.register("tinect-oauth-provider-github-settings",{template:l,props:{item:{required:!0}},computed:{callbackUrl(){return`${window.location.origin}/account/oauth/${this.item.id}/callback`}}});var r=`{% block tinect_oauth_provider_open_id_connect_settings %}
<div class="tinect-oauth-provider-open-id-connect-settings">
    <sw-container
        columns="repeat(auto-fit, minmax(250px, 1fr))"
        gap="0px 30px"
    >
        <sw-text-field
            v-model:value="item.config.clientId"
            label="Client ID"
            placeholder="OIDC Client ID"
            required
        ></sw-text-field>
        <sw-password-field
            v-model:value="item.config.clientSecret"
            label="Client Secret"
            placeholder="OIDC Client Secret"
            required
        ></sw-password-field>
    </sw-container>
    <sw-container
        columns="1fr"
        gap="0px 30px"
    >
        <sw-text-field
            v-model:value="item.config.discoveryDocumentUrl"
            label="Discovery Document URL"
            placeholder="https://accounts.example.com/.well-known/openid-configuration"
            help-text="If provided, the authorization, token and userinfo endpoints will be fetched automatically."
        ></sw-text-field>
    </sw-container>
    <sw-container
        columns="repeat(auto-fit, minmax(250px, 1fr))"
        gap="0px 30px"
    >
        <sw-text-field
            v-model:value="item.config.authorization_endpoint"
            label="Authorization Endpoint"
            placeholder="https://accounts.example.com/authorize"
            help-text="Leave empty to use the discovery document."
        ></sw-text-field>
        <sw-text-field
            v-model:value="item.config.token_endpoint"
            label="Token Endpoint"
            placeholder="https://accounts.example.com/token"
            help-text="Leave empty to use the discovery document."
        ></sw-text-field>
        <sw-text-field
            v-model:value="item.config.userinfo_endpoint"
            label="Userinfo Endpoint"
            placeholder="https://accounts.example.com/userinfo"
            help-text="Leave empty to use the discovery document."
        ></sw-text-field>
    </sw-container>
    <sw-container
        columns="1fr"
        gap="0px 30px"
    >
        <sw-text-field
            v-model:value="item.config.scopes"
            label="Scopes"
            placeholder="openid email profile"
            help-text="Space-separated list of scopes. Defaults to: openid email profile"
        ></sw-text-field>
    </sw-container>
</div>
{% endblock %}`;var{Component:v}=Shopware;v.register("tinect-oauth-provider-open-id-connect-settings",{template:r,props:{item:{required:!0}}});var a=`{% block tinect_oauth_provider_microsoft_entra_settings %}
<div class="tinect-oauth-provider-microsoft-entra-settings">
    <sw-container
        columns="repeat(auto-fit, minmax(250px, 1fr))"
        gap="0px 30px"
    >
        <sw-text-field
            v-model:value="item.config.tenantId"
            label="Tenant ID"
            placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
            required
        ></sw-text-field>
        <sw-text-field
            v-model:value="item.config.clientId"
            label="Client ID"
            placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
            required
        ></sw-text-field>
        <sw-password-field
            v-model:value="item.config.clientSecret"
            label="Client Secret"
            placeholder="Microsoft Entra Client Secret"
            required
        ></sw-password-field>
    </sw-container>
    <sw-container
        columns="1fr"
        gap="0px 30px"
        style="margin-top: 16px;"
    >
        <sw-text-field-deprecated
            :value="callbackUrl"
            label="Redirect URI"
            :disabled="true"
            :copyable="true"
            :copyable-tooltip="true"
        ></sw-text-field-deprecated>
    </sw-container>
</div>
{% endblock %}`;var{Component:_}=Shopware;_.register("tinect-oauth-provider-microsoft-entra-settings",{template:a,props:{item:{required:!0}},computed:{callbackUrl(){return`${window.location.origin}/account/oauth/${this.item.id}/callback`}}});var c=`{% block tinect_oauth_provider_google_mail_settings %}
<div class="tinect-oauth-provider-google-mail-settings">
    <sw-container
        columns="repeat(auto-fit, minmax(250px, 1fr))"
        gap="0px 30px"
    >
        <sw-text-field
            v-model:value="item.config.clientId"
            label="Client ID"
            placeholder="Google OAuth2 Client ID"
            required
        ></sw-text-field>
        <sw-password-field
            v-model:value="item.config.clientSecret"
            label="Client Secret"
            placeholder="Google OAuth2 Client Secret"
            required
        ></sw-password-field>
    </sw-container>
    <sw-container
        columns="1fr"
        gap="0px 30px"
        style="margin-top: 16px;"
    >
        <sw-text-field-deprecated
            :value="callbackUrl"
            label="Authorized redirect URI"
            :disabled="true"
            :copyable="true"
            :copyable-tooltip="true"
        ></sw-text-field-deprecated>
    </sw-container>
</div>
{% endblock %}`;var{Component:y}=Shopware;y.register("tinect-oauth-provider-google-mail-settings",{template:c,props:{item:{required:!0}},computed:{callbackUrl(){return`${window.location.origin}/account/oauth/${this.item.id}/callback`}}});})();

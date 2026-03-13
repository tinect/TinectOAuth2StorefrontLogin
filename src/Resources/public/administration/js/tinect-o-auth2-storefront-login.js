(()=>{Shopware.Service("privileges").addPrivilegeMappingEntry({category:"permissions",parent:"settings",key:"tinect_oauth_storefront_client",roles:{viewer:{privileges:["tinect_oauth_storefront_client:read","tinect_oauth_storefront_customer_key:read"],dependencies:[]},editor:{privileges:["tinect_oauth_storefront_client:update","tinect_oauth_storefront_customer_key:delete"],dependencies:["tinect_oauth_storefront_client.viewer"]},creator:{privileges:["tinect_oauth_storefront_client:create"],dependencies:["tinect_oauth_storefront_client.editor"]},deleter:{privileges:["tinect_oauth_storefront_client:delete"],dependencies:["tinect_oauth_storefront_client.viewer"]}}});var n=`{% block tinect_oauth_storefront_client_listing_page %}
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
        <sw-button @click="onShowConnections">
            {{ $tc('tinect-oauth-storefront-client.list.buttonConnections') }}
        </sw-button>
        <sw-button
            v-if="acl.can('tinect_oauth_storefront_client.creator')"
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
{% endblock %}`;var{Component:g,Mixin:_}=Shopware,{Criteria:r}=Shopware.Data;g.register("tinect-oauth-storefront-client-listing-page",{template:n,inject:["acl","repositoryFactory"],mixins:[_.getByName("listing")],data(){return{repository:null,clients:null,isLoading:!0,sortBy:"name",sortDirection:"ASC"}},metaInfo(){return{title:this.$createTitle()}},computed:{columns(){return[{property:"name",label:this.$tc("tinect-oauth-storefront-client.list.columnName"),routerLink:"tinect.oauth.storefront.client.edit",primary:!0,allowResize:!0},{property:"provider",label:this.$tc("tinect-oauth-storefront-client.list.columnProvider"),allowResize:!0},{property:"active",label:this.$tc("tinect-oauth-storefront-client.list.columnActive"),allowResize:!0}]}},created(){this.repository=this.repositoryFactory.create("tinect_oauth_storefront_client"),this.getList()},methods:{getList(){this.isLoading=!0;let t=new r(this.page,this.limit);t.addSorting(r.sort(this.sortBy,this.sortDirection)),this.term&&t.setTerm(this.term),this.repository.search(t,Shopware.Context.api).then(e=>{this.clients=e,this.total=e.total,this.isLoading=!1})},onCreateNew(){this.$router.push({name:"tinect.oauth.storefront.client.create"})},onShowConnections(){this.$router.push({name:"tinect.oauth.storefront.client.connections"})}}});var c=`{% block tinect_oauth_storefront_client_edit_page %}
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
            v-if="!isCreateMode && acl.can('tinect_oauth_storefront_client.editor')"
            class="sw-settings-login-registration__save-action"
            :is-loading="isLoading"
            :process-success="isSaveSuccessful"
            variant="primary"
            @click="onSave"
        >
            {{ $tc('tinect-oauth-storefront-client.edit.buttonSave') }}
        </sw-button-process>
        <sw-button
            v-else-if="isCreateMode && acl.can('tinect_oauth_storefront_client.creator')"
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
                        :disabled="!acl.can('tinect_oauth_storefront_client.editor')"
                        required
                    ></sw-text-field>
                    <sw-single-select
                        v-model:value="client.provider"
                        :options="providerOptions"
                        :label="$tc('tinect-oauth-storefront-client.create.labelProvider')"
                        :placeholder="$tc('tinect-oauth-storefront-client.create.placeholderProvider')"
                        :disabled="!acl.can('tinect_oauth_storefront_client.editor')"
                        required
                    ></sw-single-select>
                    <sw-switch-field
                        v-model:value="client.active"
                        :label="$tc('tinect-oauth-storefront-client.edit.labelActive')"
                        :disabled="!acl.can('tinect_oauth_storefront_client.editor')"
                    ></sw-switch-field>
                    <sw-switch-field
                        v-model:value="client.connectOnly"
                        :label="$tc('tinect-oauth-storefront-client.edit.labelConnectOnly')"
                        :help-text="$tc('tinect-oauth-storefront-client.edit.labelConnectOnlyHelp')"
                        :disabled="!acl.can('tinect_oauth_storefront_client.editor')"
                    ></sw-switch-field>
                    <sw-switch-field
                        v-model:value="client.trustEmail"
                        :label="$tc('tinect-oauth-storefront-client.edit.labelTrustEmail')"
                        :help-text="$tc('tinect-oauth-storefront-client.edit.labelTrustEmailHelp')"
                        :disabled="!acl.can('tinect_oauth_storefront_client.editor')"
                    ></sw-switch-field>
                    <sw-switch-field
                        v-model:value="client.updateEmailOnLogin"
                        :disabled="client.trustEmail || !acl.can('tinect_oauth_storefront_client.editor')"
                        :label="$tc('tinect-oauth-storefront-client.edit.labelUpdateEmailOnLogin')"
                        :help-text="$tc('tinect-oauth-storefront-client.edit.labelUpdateEmailOnLoginHelp')"
                    ></sw-switch-field>
                    <sw-switch-field
                        v-model:value="client.disablePasswordLogin"
                        :label="$tc('tinect-oauth-storefront-client.edit.labelDisablePasswordLogin')"
                        :help-text="$tc('tinect-oauth-storefront-client.edit.labelDisablePasswordLoginHelp')"
                        :disabled="!acl.can('tinect_oauth_storefront_client.editor')"
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
                v-if="!isCreateMode && acl.can('tinect_oauth_storefront_client.deleter')"
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
{% endblock %}
`;var{Component:b,Mixin:x}=Shopware;b.register("tinect-oauth-storefront-client-edit-page",{template:c,inject:["acl","repositoryFactory"],mixins:[x.getByName("notification")],props:{clientId:{type:String,required:!1,default:null}},data(){return{client:null,isLoading:!1,isSaveSuccessful:!1,showDeleteModal:!1,providerOptions:[{value:"github",label:this.$tc("tinect-oauth-storefront-client.provider.github")},{value:"open_id_connect",label:this.$tc("tinect-oauth-storefront-client.provider.open_id_connect")},{value:"microsoft_entra",label:this.$tc("tinect-oauth-storefront-client.provider.microsoft_entra")},{value:"google_mail",label:this.$tc("tinect-oauth-storefront-client.provider.google_mail")}]}},metaInfo(){return{title:this.$createTitle()}},computed:{isCreateMode(){return!this.clientId},repository(){return this.repositoryFactory.create("tinect_oauth_storefront_client")},providerSettingsComponent(){return!this.client||!this.client.provider?null:`tinect-oauth-provider-${this.client.provider.replace(/_/g,"-")}-settings`}},watch:{clientId(t){t&&this.loadClient()},"client.trustEmail"(t){t&&this.client&&(this.client.updateEmailOnLogin=!1)}},created(){this.isCreateMode?(this.client=this.repository.create(Shopware.Context.api),this.client.active=!1,this.client.connectOnly=!1,this.client.trustEmail=!1,this.client.updateEmailOnLogin=!1,this.client.config={}):this.loadClient()},methods:{loadClient(){this.isLoading=!0,this.repository.get(this.clientId,Shopware.Context.api).then(t=>{this.client=t,this.client.config||(this.client.config={}),this.isLoading=!1})},onSave(){this.isCreateMode&&(!this.client.name||!this.client.provider)||(this.isLoading=!0,this.isSaveSuccessful=!1,this.repository.save(this.client,Shopware.Context.api).then(()=>{if(this.isCreateMode){this.isLoading=!1,this.$router.push({name:"tinect.oauth.storefront.client.edit",params:{id:this.client.id}});return}this.isLoading=!1,this.isSaveSuccessful=!0,this.createNotificationSuccess({message:this.$tc("tinect-oauth-storefront-client.notification.saveSuccess")})}).catch(()=>{this.isLoading=!1,this.createNotificationError({message:this.$tc("tinect-oauth-storefront-client.notification.saveError")})}))},onDelete(){this.showDeleteModal=!1,this.isLoading=!0,this.repository.delete(this.clientId,Shopware.Context.api).then(()=>{this.$router.push({name:"tinect.oauth.storefront.client.list"})}).catch(()=>{this.isLoading=!1,this.createNotificationError({message:this.$tc("tinect-oauth-storefront-client.notification.deleteError")})})},onBack(){this.$router.push({name:"tinect.oauth.storefront.client.list"})}}});var l=`{% block tinect_oauth_storefront_connections_page %}
<sw-page class="tinect-oauth-storefront-connections-page">
    <template #smart-bar-header>
        <h2>{{ $tc('tinect-oauth-storefront-client.connections.title') }}</h2>
    </template>

    <template #smart-bar-actions>
        <sw-button @click="onBack">
            {{ $tc('tinect-oauth-storefront-client.connections.buttonBack') }}
        </sw-button>
        <sw-button @click="getList">
            {{ $tc('tinect-oauth-storefront-client.connections.buttonRefresh') }}
        </sw-button>
    </template>

    <template #content>
        <sw-data-grid
            v-if="connections && connections.length > 0"
            :data-source="connections"
            :columns="columns"
            :is-loading="isLoading"
            :show-settings="false"
            :show-selection="false"
            :allow-inline-edit="false"
            :sort-by="sortBy"
            :sort-direction="sortDirection"
        >
            <template #column-customer.customerNumber="{ item }">
                {{ item.customer && item.customer.customerNumber }}
            </template>

            <template #column-customer.email="{ item }">
                {{ customerLabel(item.customer) }}
            </template>

            <template #column-client.provider="{ item }">
                {{ providerLabel(item.client && item.client.provider) }}
            </template>

            <template #column-createdAt="{ item }">
                {{ dateFilter(item.createdAt, { hour: '2-digit', minute: '2-digit' }) }}
            </template>

            <template #actions="{ item }">
                <sw-context-menu-item
                    v-if="acl.can('tinect_oauth_storefront_client.editor')"
                    variant="danger"
                    @click="onDeleteConnection(item.id)"
                >
                    {{ $tc('tinect-oauth-storefront-client.connections.actionDisconnect') }}
                </sw-context-menu-item>
            </template>
        </sw-data-grid>

        <sw-empty-state
            v-else-if="!isLoading"
            :title="$tc('tinect-oauth-storefront-client.connections.emptyState')"
            :show-description="false"
        ></sw-empty-state>

        <sw-confirm-modal
            v-if="showDeleteModal"
            type="delete"
            :text="$tc('tinect-oauth-storefront-client.connections.deleteConfirmText')"
            @confirm="onConfirmDelete"
            @cancel="onCancelDelete"
            @close="onCancelDelete"
        ></sw-confirm-modal>
    </template>
</sw-page>
{% endblock %}
`;var{Component:C,Mixin:s}=Shopware,{Criteria:a}=Shopware.Data;C.register("tinect-oauth-storefront-connections-page",{template:l,inject:["acl","repositoryFactory"],mixins:[s.getByName("listing"),s.getByName("notification")],data(){return{connections:null,isLoading:!0,sortBy:"createdAt",sortDirection:"DESC",showDeleteModal:!1,deleteConnectionId:null}},computed:{repository(){return this.repositoryFactory.create("tinect_oauth_storefront_customer_key")},dateFilter(){return Shopware.Filter.getByName("date")},columns(){return[{property:"customer.customerNumber",label:this.$tc("tinect-oauth-storefront-client.connections.columnCustomerNumber"),allowResize:!0},{property:"customer.email",label:this.$tc("tinect-oauth-storefront-client.connections.columnCustomer"),allowResize:!0,primary:!0},{property:"client.name",label:this.$tc("tinect-oauth-storefront-client.connections.columnClient"),allowResize:!0},{property:"client.provider",label:this.$tc("tinect-oauth-storefront-client.connections.columnProvider"),allowResize:!0},{property:"createdAt",label:this.$tc("tinect-oauth-storefront-client.connections.columnConnectedSince"),allowResize:!0}]}},created(){this.getList()},methods:{getList(){this.isLoading=!0;let t=new a(this.page,this.limit);t.addAssociation("client"),t.addAssociation("customer"),t.addSorting(a.sort(this.sortBy,this.sortDirection)),this.term&&t.setTerm(this.term),this.repository.search(t,Shopware.Context.api).then(e=>{this.connections=e,this.total=e.total,this.isLoading=!1}).catch(()=>{this.isLoading=!1})},onDeleteConnection(t){this.deleteConnectionId=t,this.showDeleteModal=!0},onConfirmDelete(){this.showDeleteModal=!1,this.isLoading=!0,this.repository.delete(this.deleteConnectionId,Shopware.Context.api).then(()=>{this.deleteConnectionId=null,this.createNotificationSuccess({message:this.$tc("tinect-oauth-storefront-client.connections.deleteSuccess")}),this.getList()}).catch(()=>{this.isLoading=!1,this.deleteConnectionId=null,this.createNotificationError({message:this.$tc("tinect-oauth-storefront-client.connections.deleteError")})})},onCancelDelete(){this.showDeleteModal=!1,this.deleteConnectionId=null},onBack(){this.$router.push({name:"tinect.oauth.storefront.client.list"})},providerLabel(t){if(!t)return"";let e=`tinect-oauth-storefront-client.provider.${t}`,i=this.$tc(e);return i!==e?i:t},customerLabel(t){if(!t)return"\u2014";let e=[t.firstName,t.lastName].filter(Boolean).join(" ");return e?`${e} (${t.email})`:t.email}}});var{Module:$}=Shopware;$.register("tinect-oauth-storefront-client",{type:"plugin",name:"tinect-oauth-storefront-client.title",title:"tinect-oauth-storefront-client.title",description:"tinect-oauth-storefront-client.title",color:"#ff68b4",icon:"regular-plug",routes:{list:{component:"tinect-oauth-storefront-client-listing-page",path:"list",meta:{privilege:"tinect_oauth_storefront_client.viewer"}},create:{component:"tinect-oauth-storefront-client-edit-page",path:"create",meta:{privilege:"tinect_oauth_storefront_client.creator"}},edit:{component:"tinect-oauth-storefront-client-edit-page",path:"edit/:id",meta:{privilege:"tinect_oauth_storefront_client.editor"},props:{default(t){return{clientId:t.params.id}}}},connections:{component:"tinect-oauth-storefront-connections-page",path:"connections",meta:{privilege:"tinect_oauth_storefront_client.viewer"}}},settingsItem:[{name:"tinect-oauth-storefront-client",to:"tinect.oauth.storefront.client.list",label:"tinect-oauth-storefront-client.title",group:"plugins",icon:"regular-plug",privilege:"tinect_oauth_storefront_client.viewer"}]});var d=`<sw-card
    :title="$tc('tinect-oauth-storefront-client.customerDetail.cardTitle')"
    :is-loading="isLoading"
>
    <sw-data-grid
        v-if="connectedProviders && connectedProviders.length > 0"
        :data-source="connectedProviders"
        :columns="columns"
        :show-settings="false"
        :show-selection="false"
        :allow-inline-edit="false"
    >
        <template #column-client.provider="{ item }">
            {{ providerLabel(item.client && item.client.provider) }}
        </template>

        <template #column-createdAt="{ item }">
            {{ dateFilter(item.createdAt, { hour: '2-digit', minute: '2-digit' }) }}
        </template>

        <template #actions="{ item }">
            <sw-context-menu-item
                v-if="acl.can('tinect_oauth_storefront_client.editor')"
                variant="danger"
                @click="onDisconnect(item.id)"
            >
                {{ $tc('tinect-oauth-storefront-client.customerDetail.actionDisconnect') }}
            </sw-context-menu-item>
        </template>
    </sw-data-grid>

    <sw-empty-state
        v-else-if="!isLoading"
        :title="$tc('tinect-oauth-storefront-client.customerDetail.emptyState')"
        :show-description="false"
    ></sw-empty-state>

    <sw-confirm-modal
        v-if="showDeleteModal"
        type="delete"
        :text="$tc('tinect-oauth-storefront-client.customerDetail.disconnectConfirmText')"
        @confirm="onConfirmDisconnect"
        @cancel="onCancelDisconnect"
        @close="onCancelDisconnect"
    ></sw-confirm-modal>
</sw-card>
`;var{Component:S,Mixin:D}=Shopware,{Criteria:o}=Shopware.Data;S.register("tinect-oauth-customer-connected-providers",{template:d,inject:["acl","repositoryFactory"],mixins:[D.getByName("notification")],props:{customerId:{type:String,required:!0}},data(){return{connectedProviders:[],isLoading:!1,showDeleteModal:!1,deleteKeyId:null}},computed:{dateFilter(){return Shopware.Filter.getByName("date")},repository(){return this.repositoryFactory.create("tinect_oauth_storefront_customer_key")},columns(){return[{property:"client.name",label:this.$tc("tinect-oauth-storefront-client.customerDetail.columnName"),primary:!0,allowResize:!0},{property:"client.provider",label:this.$tc("tinect-oauth-storefront-client.customerDetail.columnProvider"),allowResize:!0},{property:"createdAt",label:this.$tc("tinect-oauth-storefront-client.customerDetail.columnConnectedSince"),allowResize:!0}]}},created(){this.loadProviders()},methods:{loadProviders(){this.isLoading=!0;let t=new o;t.addFilter(o.equals("customerId",this.customerId)),t.addAssociation("client"),t.addSorting(o.sort("createdAt","ASC")),this.repository.search(t,Shopware.Context.api).then(e=>{this.connectedProviders=e,this.isLoading=!1}).catch(()=>{this.isLoading=!1})},onDisconnect(t){this.deleteKeyId=t,this.showDeleteModal=!0},onConfirmDisconnect(){this.showDeleteModal=!1,this.isLoading=!0,this.repository.delete(this.deleteKeyId,Shopware.Context.api).then(()=>{this.deleteKeyId=null,this.createNotificationSuccess({message:this.$tc("tinect-oauth-storefront-client.customerDetail.disconnectSuccess")}),this.loadProviders()}).catch(()=>{this.isLoading=!1,this.deleteKeyId=null,this.createNotificationError({message:this.$tc("tinect-oauth-storefront-client.customerDetail.disconnectError")})})},onCancelDisconnect(){this.showDeleteModal=!1,this.deleteKeyId=null},providerLabel(t){let e=`tinect-oauth-storefront-client.provider.${t}`,i=this.$tc(e);return i!==e?i:t}}});var u=`{% block sw_customer_detail_base_info_holder %}
{% parent %}
<tinect-oauth-customer-connected-providers
    v-if="customer && customer.id && acl.can('tinect_oauth_storefront_client.viewer')"
    :customer-id="customer.id"
></tinect-oauth-customer-connected-providers>
{% endblock %}
`;Shopware.Component.override("sw-customer-detail-base",{template:u});var p=`{% block tinect_oauth_provider_github_settings %}
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
{% endblock %}`;var{Component:M}=Shopware;M.register("tinect-oauth-provider-github-settings",{template:p,props:{item:{required:!0}},computed:{callbackUrl(){return`${window.location.origin}/account/oauth/${this.item.id}/callback`}}});var h=`{% block tinect_oauth_provider_open_id_connect_settings %}
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
{% endblock %}`;var{Component:N}=Shopware;N.register("tinect-oauth-provider-open-id-connect-settings",{template:h,props:{item:{required:!0}}});var m=`{% block tinect_oauth_provider_microsoft_entra_settings %}
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
{% endblock %}`;var{Component:B}=Shopware;B.register("tinect-oauth-provider-microsoft-entra-settings",{template:m,props:{item:{required:!0}},computed:{callbackUrl(){return`${window.location.origin}/account/oauth/${this.item.id}/callback`}}});var f=`{% block tinect_oauth_provider_google_mail_settings %}
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
{% endblock %}`;var{Component:P}=Shopware;P.register("tinect-oauth-provider-google-mail-settings",{template:f,props:{item:{required:!0}},computed:{callbackUrl(){return`${window.location.origin}/account/oauth/${this.item.id}/callback`}}});})();

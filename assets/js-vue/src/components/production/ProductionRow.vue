<template>
    <tr :class="{'is-disabled': disabled}">
        <td>
            <span class="text-nowrap">{{ order.Agreement.orderNumber || order.id }}</span>
            <tags-indicator :logs="order.tags"/>
            <div class="badge" :class="getAgreementStatusClass(order.status)" v-if="order.status !== 10">{{ $t(getAgreementStatusName(order.status)) }}</div>
        </td>

        <td class="text-nowrap">
            {{ order.confirmedDate | formatDate('YYYY-MM-DD') }}
        </td>

        <td>
            <span v-if="order.Agreement.user && order.Agreement.user.userFullName">
                {{ order.Agreement.user.userFullName }}
            </span>
            <span v-else class="text-muted text-sm text-nowrap opacity-75">
                <i class="fa fa-ban mr-1" /> {{ $t('noData') }}
            </span>
        </td>

        <td>
            {{ __mixin_customerName(order.Agreement.Customer) }}
        </td>

        <td>
            {{ order.Product.name }}
            <tooltip v-if="order.description">
                <i slot="visible-content" class="fa fa-info-circle hasTooltip"/>
                <div slot="tooltip-content" class="text-left" v-html="__mixin_convertNewlinesToHtml(order.description)"></div>
            </tooltip>
        </td>

        <td v-text="order.factor" class="text-center" v-if="userCanProduction()"/>

        <td class="prod" v-for="(production, prodKey) in order.productions" v-if="['dpt01', 'dpt02', 'dpt03', 'dpt04', 'dpt05'].indexOf(production.departmentSlug) !== -1">
            <div class="task">
                <div class="d-flex flex-column gap-2">
                    <b-dropdown
                            :text="$t(getStatusData(production.status).name)"
                            size="sm"
                            class="w-100"
                            :class="getStatusData(production.status).className"
                            variant="light"
                            split-variant=""
                            :disabled="disabled"
                    >
                        <b-dropdown-item
                                v-for="status in helpers.statusesPerTaskType(production.departmentSlug)"
                                :value="status.value"
                                :key="status.value"
                                :disabled="!userCanProduction()"
                                @click="updateProduction(production, status.value)"
                        >{{ $t(status.name) }}</b-dropdown-item>
                    </b-dropdown>
                    <tooltip v-if="production.dateEnd">
                        <template #visible-content>
                            <div class="text-right text-sm text-nowrap hasTooltip">
                                <i class="fa fa-clock-o mr-1" />
                                <span v-if="production.dateEnd">{{ production.dateEnd | formatDate('YYYY-MM-DD') }}</span>
                                <span v-else>{{ $t('noData') }}</span>
                            </div>
                        </template>
                        <div slot="tooltip-content" class="text-left">
                            {{ $t('realisationDateFor') }} {{ getDepartmentName(production.departmentSlug) }}:
                            {{ production.dateEnd | formatDate('YYYY-MM-DD') }}
                        </div>
                    </tooltip>
                    <div v-else class="text-right text-sm text-nowrap opacity-75">
                        <i class="fa fa-clock-o mr-1" />
                        {{ $t('noData') }}
                    </div>

                </div>
                <div>
                    <production-task-notification
                        :date-start="production.dateStart"
                        :date-end="production.dateEnd"
                        :status="production.status"
                        :isStartDelayed="production.isStartDelayed"
                        :isCompleted="production.isCompleted"
                        :date-deadline="order.confirmedDate"
                    />
                </div>
            </div>
        </td>

        <td>
            <button class="btn btn-light" style="padding: 0 0.5rem" v-if="hasDetails" @click.prevent="$emit('expandToggle', order.id)">

                <span v-if="order.Agreement.attachments.length > 0">
                    <i class="fa fa-paperclip sb-color"/>
                    <span class="badge badge-pill">{{ order.Agreement.attachments.length }}</span>
                </span>

                <span v-if="getCustomTasks(order.productions).length && userCanProduction()">
                    <i class="fa fa-check-square-o sb-color"/>
                    <span class="badge badge-pill">{{ getCustomTasks(order.productions).length }}</span>
                </span>

            </button>
        </td>

        <td>
            <line-actions :line="order" @lineChanged="$emit('lineChanged')" :disabled="disabled"/>
        </td>
    </tr>
</template>

<script>

    import ProductionRowBase from "./ProductionRowBase";
    import Tooltip from "../base/Tooltip";
    import LineActions from "../common/LineActions";
    import TagsIndicator from "../../modules/tags/widget/TagsIndicator";
    import helpers, { getDepartmentName } from "../../helpers";
    import ProductionTaskNotification from "./ProductionTaskNotification";

    export default {
        name: "ProductionRow",

        extends: ProductionRowBase,

        components: { Tooltip, LineActions, TagsIndicator, ProductionTaskNotification },

        data() {
            return {}
        },

        methods: {
            getAgreementStatusClass(statusId) {
                let className = '';
                switch (parseInt(statusId)) {
                    case 5:
                        className = 'badge-info';
                        break;
                    case 10:
                        className = 'badge-primary';
                        break;
                    case 15:
                        className = 'badge-warning';
                        break;
                    case 20:
                        className = 'badge-success';
                        break;

                    default:
                        className = 'badge-primary'
                }
                return className;
            },
            getAgreementStatusName(statusId) {
                return this.statuses[statusId];
            },

            getStatusData(status) {
                return helpers.statuses.find(i => i.value === parseInt(status));
            },

            updateProduction(production, newStatus) {
                production.status = newStatus;
                this.$emit('statusUpdated', { id: production.id, status: newStatus});
            },

            getDepartmentName,
        },
        computed: {
            hasDetails() {
                return (this.order.Agreement.attachments.length > 0) || (this.getCustomTasks(this.order.productions).length && this.userCanProduction());
            }
        }
    }
</script>

<style lang="scss">

    .is-disabled {
        opacity: 0.5
    }

    .b-dropdown, .b-dropdown.show {

        &.dropdown-white button,
        &.dropdown-white button:hover, &.dropdown-white button:active, &.dropdown-white button:focus
        {
            background-color: #E7E7E7;
            color: #333;
        }

        &.dropdown-orange button,
        &.dropdown-orange button:hover, &.dropdown-orange button:active, &.dropdown-orange button:focus
        {
            background-color: #FFA07A;
        }

        &.dropdown-blue button,
        &.dropdown-blue button:hover, &.dropdown-blue button:active, &.dropdown-blue button:focus
        {
            background-color: #87CEFA;
        }

        &.dropdown-green1 button,
        &.dropdown-green1 button:hover, &.dropdown-green1 button:active, &.dropdown-green1 button:focus
        {
            background-color: #8FBC8F;
        }

        &.dropdown-green2 button,
        &.dropdown-green2 button:hover, &.dropdown-green2 button:active, &.dropdown-green2 button:focus
        {
            background-color: #419D78;
            color: #FFFFFF;
        }
    }

</style>
<template>

    <tr>
        <td>
            {{ order.Agreement.orderNumber || order.id }}
            <div class="badge" :class="getAgreementStatusClass(order.status)" v-if="order.status !== 10">{{ $t(getAgreementStatusName(order.status)) }}</div>
        </td>

        <td>
            {{ order.confirmedDate | formatDate('YYYY-MM-DD') }}
        </td>

        <td v-if="userCanProduction()">
            <span v-if="order.factorBindDate">{{ order.factorBindDate | formatDate('YYYY-MM') }}</span>
            <span v-else class="badge badge-pill badge-danger">
                <i class="fa fa-exclamation-circle"></i> {{ $t('orders.resourceAssignmentNotSpecified') }}
            </span>
        </td>

        <td>
            {{ __mixin_customerName(order.Agreement.Customer) }}
        </td>

        <td>
            {{ order.Product.name }}
            <tooltip v-if="order.description && order.description.length > 0">
                <i slot="visible-content" class="fa fa-info-circle hasTooltip"/>
                <div slot="tooltip-content" class="text-left" v-html="__mixin_convertNewlinesToHtml(order.description)"></div>
            </tooltip>
        </td>

        <td class="text-center" v-if="userCanProduction()">
            <span>{{ order.factor }}</span>
        </td>

        <td class="tasks" v-for="(production, prodKey) in order.productions" v-if="['dpt01', 'dpt02', 'dpt03', 'dpt04', 'dpt05'].indexOf(production.departmentSlug) !== -1">
            <div class="task">
                <b-dropdown
                        :text="$t(getStatusData(production.status).name)"
                        size="sm"
                        :class="getStatusData(production.status).className"
                        variant="light"

                >
                    <b-dropdown-item
                            v-for="status in helpers.statusesPerTaskType(production.departmentSlug)"
                            :value="status.value"
                            :key="status.value"
                            :disabled="!userCanProduction()"
                            @click="updateProduction(production, status.value)"
                    >{{ $t(status.name) }}</b-dropdown-item>
                </b-dropdown>

            </div>
        </td>

        <td>
            <button class="btn btn-light" style="padding: 0 0.5rem" v-if="hasDetails" @click.prevent="$emit('expandToggle', order.id)">

                <span v-if="order.Agreement.attachments && order.Agreement.attachments.length > 0">
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
            <line-actions :line="order" @lineChanged="$emit('lineChanged')"/>
        </td>

    </tr>

</template>

<script>

    import ProductionRowBase from "./ProductionRowBase";
    import Tooltip from "../base/Tooltip";
    import LineActions from "../common/LineActions";
    import helpers from "../../helpers";

    export default {
        name: "ProductionRow",

        extends: ProductionRowBase,

        components: { Tooltip, LineActions },

        data() {
            return {}
        },

        methods: {
            getAgreementStatusClass(statusId) {
                let className = '';
                switch (parseInt(statusId)) {
                    case 5:
                        className = 'badge-danger';
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
            }

        },
        computed: {
            hasDetails() {
                return (this.order.Agreement.attachments && this.order.Agreement.attachments.length > 0) || (this.getCustomTasks(this.order.productions).length && this.userCanProduction());
            }
        }
    }
</script>

<style lang="scss">

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
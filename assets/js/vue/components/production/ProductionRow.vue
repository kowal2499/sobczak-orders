<template>

    <tr>
        <td>
            {{ order.header.orderNumber || order.line.id }}
            <div class="badge" :class="getAgreementStatusClass(order.line.status)" v-if="order.line.status !== 10">{{ $t(getAgreementStatusName(order.line.status)) }}</div>
        </td>

        <td>
            {{ order.line.confirmedDate }}
        </td>

        <td>
            {{ __mixin_customerName(order.customer) }}
        </td>

        <td>
            {{ order.product.name }}
            <tooltip v-if="order.line.description.length > 0">
                <i slot="visible-content" class="fa fa-info-circle hasTooltip"/>
                <div slot="tooltip-content" class="text-left" v-html="__mixin_convertNewlinesToHtml(order.line.description)"></div>
            </tooltip>
        </td>

        <td v-text="order.line.factor" class="text-center" v-if="userCanProduction()"/>

        <td class="tasks" v-for="(production, prodKey) in order.production.data" v-if="['dpt01', 'dpt02', 'dpt03', 'dpt04', 'dpt05'].indexOf(production.departmentSlug) !== -1">
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
            <button class="btn btn-light" style="padding: 0 0.5rem" v-if="hasDetails" @click.prevent="$emit('expandToggle', order.line.id)">

                <span v-if="order.header.attachments.length > 0">
                    <i class="fa fa-paperclip sb-color"/>
                    <span class="badge badge-pill">{{ order.header.attachments.length }}</span>
                </span>

                <span v-if="getCustomTasks(order.production.data).length && userCanProduction()">
                    <i class="fa fa-check-square-o sb-color"/>
                    <span class="badge badge-pill">{{ getCustomTasks(order.production.data).length }}</span>
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
                switch (statusId) {
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
                return helpers.statuses.find(i => i.value === status);
            },

            updateProduction(production, newStatus) {
                production.status = newStatus;
                this.$emit('statusUpdated', { id: production.id, status: newStatus});
            }

        },
        computed: {
            hasDetails() {
                return (this.order.header.attachments.length > 0) || (this.getCustomTasks(this.order.production.data).length && this.userCanProduction());
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
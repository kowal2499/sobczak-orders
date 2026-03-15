<template>
    <tr :class="{'is-disabled': disabled}">
        <td class="d-flex flex-column align-items-start gap-2">
            <line-actions :line="order" @lineChanged="$emit('lineChanged')" :disabled="disabled"/>

            <button class="btn btn-light d-flex flex-nowrap gap-2" style="padding: 0 0.5rem" v-if="userCanProduction && hasDetails" @click.prevent="$emit('expandToggle', order.agreementLineId)">
                <span v-if="order.attachments.length" class="d-flex gap-1">
                    <i class="fa fa-paperclip sb-color"/>
                    <span class="badge badge-pill text-primary">{{ order.attachments.length }}</span>
                </span>

                <span v-if="getCustomTasks(order.productions).length && userCanProduction" class="d-flex gap-1">
                    <i class="fa fa-check-square-o sb-color"/>
                    <span class="badge badge-pill text-primary">{{ getCustomTasks(order.productions).length }}</span>
                </span>
            </button>
        </td>

        <td>
            <span class="text-nowrap">{{ order.agreement.orderNumber || order.agreementLineId }}</span>
            <div class="d-inline-flex">
                <tag
                    v-for="(tag, key) in order.tags"
                    :key="key"
                    :color="tag.color"
                    :icon="tag.icon"
                    :name="tag.name"
                />
            </div>
            <div class="badge" :class="getAgreementStatusClass(order.status)" v-if="order.status !== 10">{{ $t(getAgreementStatusName(order.status)) }}</div>
        </td>

        <td class="text-nowrap" v-if="$user.can('production.show.production_date')">
            {{ order.confirmedDate | formatDate('YYYY-MM-DD') }}
        </td>

        <td>
            <span v-if="order.user.name">
                {{ order.user.name }}
            </span>
            <span v-else class="text-muted text-sm text-nowrap opacity-75">
                <i class="fa fa-ban mr-1" /> {{ $t('noData') }}
            </span>
        </td>

        <td>
            {{ order.customerName }}
        </td>

        <td>
            {{ order.product.name }}
            <tooltip v-if="order.description">
                <i slot="visible-content" class="fa fa-info-circle hasTooltip" />
                <div slot="tooltip-content" class="text-left" v-html="__mixin_convertNewlinesToHtml(order.description)" />
            </tooltip>
        </td>

        <td v-text="order.factor" class="text-center" v-if="userCanProduction" />

        <td
            v-for="production in productionsByGrants"
            v-if="['dpt01', 'dpt02', 'dpt03', 'dpt04', 'dpt05', 'dpt06'].indexOf(production.departmentSlug) !== -1"
            class="prod"
        >
            <div class="task">
                <div class="d-flex flex-column gap-1">
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
                            :disabled="!userCanProduction"
                            @click="updateProduction(production, status.value)"
                        >{{ $t(status.name) }}</b-dropdown-item>
                    </b-dropdown>

                    <div class="text-center text-nowrap">
                        <span v-if="production.dateStart">
                            {{ production.dateStart | formatDate('YYYY-MM-DD') }}
                        </span>
                        <span v-else class="text-muted text-sm text-nowrap opacity-75">
                            <i class="fa fa-ban mr-1" /> {{ $t('noData') }}
                        </span>
                    </div>

                    <div class="text-center text-nowrap">
                        <span v-if="production.dateEnd">
                            {{ production.dateEnd | formatDate('YYYY-MM-DD') }}
                        </span>
                        <span v-else class="text-muted text-sm text-nowrap opacity-75">
                            <i class="fa fa-ban mr-1" /> {{ $t('noData') }}
                        </span>
                    </div>

                    <production-task-notification
                        :date-start="production.dateStart"
                        :date-end="production.dateEnd"
                        :status="production.status"
                        :isStartDelayed="production.isStartDelayed"
                        :isCompleted="production.isCompleted"
                        :date-deadline="order.confirmedDate"
                    />

                    <FactorDisplay
                        v-if="production.factorBonus"
                        :factor-data="production.factorBonus"
                    />

                </div>
            </div>
        </td>
    </tr>
</template>

<script>
    import ProductionRowBase from "./ProductionRowBase";
    import Tooltip from "../../../components/base/Tooltip";
    import LineActions from "./LineActions2";
    import Tag from "../../tags/widget/Tag";
    import helpers, { getDepartmentName, DEPARTMENTS } from "../../../helpers";
    import ProductionTaskNotification from "../../../components/production/ProductionTaskNotification";
    import FactorDisplay from './FactorDisplay'

    export default {
        name: "ProductionRow",

        extends: ProductionRowBase,

        components: { Tooltip, LineActions, Tag, ProductionTaskNotification, FactorDisplay, },

        data() {
            return {}
        },

        computed: {
            hasDetails() {
                return (this.order.attachments.length || (this.getCustomTasks(this.order.productions).length && this.userCanProduction));
            },

            productionsByGrants() {
                const productionSlugs = DEPARTMENTS.map(d => d.slug)
                return this.order.productions.filter(prod => {
                    if (!productionSlugs.includes(prod.departmentSlug)) {
                        return true
                    }
                    const config = DEPARTMENTS.find(d => d.slug === prod.departmentSlug)
                    return this.$user.can(config.grant)
                })
            }
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
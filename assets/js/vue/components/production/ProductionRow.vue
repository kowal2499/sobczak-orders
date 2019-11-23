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
                <i slot="visible-content" class="fa fa-info-circle hasTooltip"></i>
                <div slot="tooltip-content" class="text-left" v-html="__mixin_convertNewlinesToHtml(order.line.description)"></div>
            </tooltip>
        </td>

        <td v-text="order.line.factor" class="text-center" v-if="userCanProduction"></td>

        <td class="tasks" v-for="(production, prodKey) in order.production.data" v-if="['dpt01', 'dpt02', 'dpt03', 'dpt04', 'dpt05'].indexOf(production.departmentSlug) !== -1">
            <div class="task">
                <select class="form-control"
                        v-model="production.status"
                        @change="$emit('statusUpdated', { id: production.id, status: production.status })"
                        :style="getStatusStyle(production)"
                >
                    <option
                            v-for="status in helpers.statusesPerTaskType(production.departmentSlug)"
                            :value="status.value"
                            v-text="$t(status.name)"
                            style="background-color: white"
                            :disabled="!userCanProduction"
                    ></option>
                </select>
            </div>
        </td>

        <td>
            <button class="btn btn-light" style="padding: 0 0.5rem" v-if="hasDetails" @click.prevent="$emit('expandToggle', order.line.id)">

                <span v-if="order.header.attachments.length > 0">
                    <i class="fa fa-paperclip sb-color"></i>
                    <span class="badge badge-pill">{{ order.header.attachments.length }}</span>
                </span>

                <span v-if="getCustomTasks(order.production.data).length && userCanProduction()">
                    <i class="fa fa-check-square-o sb-color"></i>
                    <span class="badge badge-pill">{{ getCustomTasks(order.production.data).length }}</span>
                </span>

            </button>
        </td>

        <td>
            <line-actions :line="order" @lineChanged="$emit('lineChanged')"></line-actions>
        </td>

    </tr>

</template>

<script>

    import ProductionRowBase from "./ProductionRowBase";
    import Tooltip from "../base/Tooltip";
    import LineActions from "../common/LineActions";

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



        },
        computed: {
            hasDetails() {
                return (this.order.header.attachments.length > 0) || (this.getCustomTasks(this.order.production.data).length && this.userCanProduction());
            }
        }
    }
</script>

<style scoped lang="scss">



</style>
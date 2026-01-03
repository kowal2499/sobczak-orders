<script>
import { defineComponent } from 'vue'
import StatusIcon from './StatusIcon.vue'
import DepartmentFactorValue from './DepartmentFactorValue.vue';
import { statuses } from '@/helpers';
export default defineComponent({
    name: 'Details',
    components: {DepartmentFactorValue, StatusIcon },
    props: {
        record: {
            type: Object,
            default: () => ({})
        },
    },
    methods: {
        panelUrl(id) {
            return `/agreement/line/${id}`;
        },
        statusName(id) {
            const status = statuses.find(status => status.value === Number(id))
            return status ? status.name : id;
        },
        statusColor(id) {
            const status = statuses.find(status => status.value === Number(id))
            return status ? status.color : id;
        }
    },
})
</script>

<template>
    <div class="details">
        <div class="d-flex justify-content-between">
        <div>
            <div><font-awesome-icon icon="user" /> {{ record.customerName }}</div>
            <div><font-awesome-icon icon="shopping-cart" /> {{ record.productName }}</div>
            <div><font-awesome-icon icon="hashtag" /> {{ record.orderNumber }}</div>
            <a :href="panelUrl(record.id)" target="_blank" class="text-decoration-none">
                <font-awesome-icon size="sm" icon="link" />
                <span>{{ $t('_agreement_line_panel') }}</span>
            </a>
        </div>
            <div>
                <div><font-awesome-icon icon="calendar-day" /> {{ record.data.production.dateStart | formatDate('YYYY-MM-DD') }} - {{ record.data.production.dateEnd | formatDate('YYYY-MM-DD') }}</div>
                <div>
                    <font-awesome-icon icon="cogs" />
                    {{ statusName(record.data.production.status) }}
                    <font-awesome-icon icon="square" :style="{color: statusColor(record.data.production.status)}" />
                </div>
                <div>
                    <DepartmentFactorValue :factorData="record.data" no-status-icon />
                </div>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.details {
    font-size: 0.75em;
    font-weight: normal;
    svg {
        color: #CCC;
        width: 20px;
    }

    background-color: #F4F4F4;
    padding: 1rem;
    border-radius: 1rem;
}
</style>
<script>
import { statuses } from '@/helpers'
import DepartmentFactorValue
    from "@/modules/dashboard/components/Metrics/ProductionMetric/components/DepartmentFactorValue.vue";

export default {
    name: "AgreementLineRmSchowcaseItem",
    components: {DepartmentFactorValue},
    props: {
        data: {
            type: Object,
            default: () => ({})
        }
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
}
</script>

<template>
    <div class="details m-3">
        <div class="d-flex justify-content-between">
            <div>
                <div><font-awesome-icon icon="user" /> {{ data.customerName }}</div>
                <div><font-awesome-icon icon="shopping-cart" /> {{ data.productName }}</div>
                <div><font-awesome-icon icon="hashtag" /> {{ data.orderNumber }}</div>
                <div>Status: {{ data.status }}</div>
                <div>Współczynnik: {{ data.factor }}</div>
                <div>Utworzył: {{ data.userName }}</div>
                <div>Data utworzenia: {{ data.agreementCreateDate }}</div>
                <div>Oczekiwana data realizacji: {{ data.confirmedDate }}</div>

                <a :href="panelUrl(data.agreementLineId)" target="_blank" class="text-decoration-none">
                    <font-awesome-icon size="sm" icon="link" />
                    <span>{{ $t('_agreement_line_panel') }}</span>
                </a>
            </div>
<!--            <div>-->
<!--                <div><font-awesome-icon icon="calendar-day" /> {{ record.data.production.dateStart | formatDate('YYYY-MM-DD') }} - {{ record.data.production.dateEnd | formatDate('YYYY-MM-DD') }}</div>-->
<!--                <div>-->
<!--                    <font-awesome-icon icon="cogs" />-->
<!--                    {{ statusName(record.data.production.status) }}-->
<!--                    <font-awesome-icon icon="square" :style="{color: statusColor(record.data.production.status)}" />-->
<!--                </div>-->
<!--                <div>-->
<!--                    <DepartmentFactorValue :factorData="record.data" no-status-icon />-->
<!--                </div>-->
<!--            </div>-->
        </div>
<!--        <pre>{{ data }}</pre>-->
    </div>
</template>

<style scoped lang="scss">

</style>
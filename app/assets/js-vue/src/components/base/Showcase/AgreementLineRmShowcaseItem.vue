<script>
import { statuses, getUserDepartments, getDepartmentName, getLocalDate } from '@/helpers'
import ShowcaseBadge from './ShowcaseBadge.vue'
import ShowcaseAgreementLineStatus from "./ShowcaseAgreementLineStatus.vue";
import DepartmentFactorValue
    from "@/modules/dashboard/components/Metrics/ProductionMetric/components/DepartmentFactorValue.vue";

export default {
    name: "AgreementLineRmShowcaseItem",
    components: {
        DepartmentFactorValue,
        ShowcaseBadge,
        ShowcaseAgreementLineStatus,
    },
    props: {
        data: {
            type: Object,
            default: () => ({})
        }
    },
    computed: {
        badges() {
            return [
                { icon: 'user', label: this.$t('_customer'), value: this.data.customerName },
                { icon: 'calendar-day', label: this.$t('_created_at'), value: this.data.agreementCreateDate },
                { icon: 'shopping-cart', label: this.$t('_product'), value: this.data.productName },
                { icon: 'calendar-check', label: this.$t('_confirmed_at'), value: this.data.confirmedDate },
                { icon: 'hashtag', label: this.$t('_order_number'), value: this.data.orderNumber },
                { icon: 'user-plus', label: this.$t('_created_by'), value: this.data.userName },
                { icon: 'cogs', label: this.$t('_factor'), value: this.$options.filters.roundFloat(this.data.factor, 2) },
            ]
        },
        productionData() {
            if (!this.data.productions || !Array.isArray(this.data.productions)) {
                return [];
            }
            const userDepartments = getUserDepartments().map(d => d.slug);
            return this.data.productions
                .filter(prod => userDepartments.includes(prod.departmentSlug))
                .map(prod => ({
                    ...prod,
                    departmentName: getDepartmentName(prod.departmentSlug),
                    statusInfo: statuses.find(s => s.value === parseInt(prod.status)) || { name: prod.status, color: '#ccc' }
                }));
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
        },
        getLocalDate
    },
}
</script>

<template>
    <div class="details m-3">
        <div class="row">
            <div v-for="(badge, index) in badges" :key="index" class="col-md-6">
                <ShowcaseBadge
                    :icon="badge.icon"
                    :label="badge.label"
                    :value="badge.value"
                    background-class="background-color-white-90"
                    class="w-100"
                />
            </div>

            <div class="col-md-6">
                <ShowcaseAgreementLineStatus :value="data.status" background-class="background-color-white-90" class="w-100" />
            </div>

            <div class="col-md-6">
                <ShowcaseBadge icon="link" :label="$t('_agreement_line_panel')" background-class="background-color-white-90" class="w-100">
                    <template #value>
                        <a :href="panelUrl(data.agreementLineId)" target="_blank" class="text-decoration-none">
                            {{ $t('_go_to_panel') }}
                        </a>
                    </template>
                </ShowcaseBadge>
            </div>
        </div>

        <div v-if="productionData.length" class="mt-4">
            <div class="accordion" role="tablist">
                <b-card v-for="prod in productionData" :key="prod.id" no-body class="mb-1" style="background-color: transparent !important; border-width: 0">
                    <b-card-header header-tag="header" class="p-1" role="tab" style="background-color: transparent !important; border-width: 0">
                        <b-button block v-b-toggle="'accordion-' + prod.id" variant="light" style="padding: 0 1rem; font-size: 0.85rem" class="text-left d-flex justify-content-between align-items-center">
                            <span>{{ prod.departmentName }}</span>
                            <span class="badge font-weight-normal" :style="{ backgroundColor: prod.statusInfo.color, color: '#000' }">
                                {{ prod.statusInfo.name }}
                            </span>
                        </b-button>
                    </b-card-header>
                    <b-collapse :id="'accordion-' + prod.id" accordion="production-accordion" role="tabpanel">
                        <b-card-body>
                            <div class="row">
                                <div class="col-md-6">
                                    <ShowcaseBadge
                                        icon="calendar-day"
                                        :label="$t('_date_start')"
                                        :value="prod.dateStart ? getLocalDate(prod.dateStart): ''"
                                        background-class="background-color-white-90"
                                    />
                                    <ShowcaseBadge
                                        icon="calendar-day"
                                        :label="$t('_date_end')"
                                        :value="prod.dateEnd ? getLocalDate(prod.dateEnd) : ''"
                                        background-class="background-color-white-90"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <ShowcaseBadge
                                        icon="cogs"
                                        :label="$t('_factor')"
                                        background-class="background-color-white-90"
                                    >
                                        <template #value>
                                            <DepartmentFactorValue :factorData="prod.factorRatio" no-status-icon/>
                                        </template>
                                    </ShowcaseBadge>
                                </div>
                            </div>
                        </b-card-body>
                    </b-collapse>
                </b-card>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
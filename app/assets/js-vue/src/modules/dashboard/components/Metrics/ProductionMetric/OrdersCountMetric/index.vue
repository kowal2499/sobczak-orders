<script>
import { defineComponent } from 'vue'
import MetricLayout from "../../MetricLayout.vue"
import Sidebar from '@/components/base/Sidebar.vue'
import BaseMetric from '../../BaseMetric.js'
import SidebarNavbar from '@/components/layout/SidebarNavbar.vue'
import Details from '../components/Details.vue'
import ProductionMetricMixin from '../../ProductionMetric/ProductionMetricMixin'
import SidebarLayout from '@/components/layout/SidebarLayout.vue'
import fields from '../fields'

import {
    getProductionPendingDetails,
    getProductionFinishedDetails,
} from "../../../../repository";

export default defineComponent({
    name: 'PendingOrdersMetric',
    extends: BaseMetric,
    mixins: [ProductionMetricMixin],

    props: {
        status: {
            type: String,
            validator: item => ['orders_pending', 'orders_finished'].includes(item),
        }
    },
    components: {
        MetricLayout, Sidebar, Details, SidebarNavbar, SidebarLayout,
    },
    computed: {
        canSeeProduction() {
            return this.$user.can(this.$privilages.CAN_PRODUCTION);
        },
        count() {
            if (!this.data) {
                return 0
            }
            return this.data && this.data[this.status] && this.data[this.status].count || 0
        },
        summary() {
            if (!this.data) {
                return 0
            }
            return this.data && this.data[this.status] && this.data[this.status].factors_summary || 0
        },
    },

    methods: {
        fetchDetails(callback) {
            this.reset()
            this.isFetchingDetails = true
            const promise = this.status === 'orders_pending'
                ? getProductionPendingDetails(null, this.filters?.dateEnd)
                : getProductionFinishedDetails(this.filters?.dateStart, this.filters?.dateEnd);

            return promise
                .then(({data}) => {
                    this.innerData = this.mapDetails(data).map(item => this.addSearchKey(item));
                    callback()
                })
                .finally(() => this.isFetchingDetails = false)
        },

        onExportExcel() {
            return this.exportExcel(this.$t(`dashboard.${this.status}`), fields, this.innerData)
        }
    },

    data: () => ({
        isFetchingDetails: false,
    })
})
</script>

<template>
    <MetricLayout :is-busy="isBusy">
        <template #title>
            {{ $t(`dashboard.${status}`) }}
        </template>

        <template #default>
            <Sidebar
                v-if="canSeeProduction"
                :title="$t(`dashboard.${status}`)"
                sidebar-class="size-100 size-lg-75"
            >
                <template #sidebar-action="{ open }">
                    <font-awesome-icon icon="spinner" spin v-if="isFetchingDetails" />
                    <a href="#" @click.prevent="fetchDetails(open)" v-else>
                        <div class="d-inline-block">
                            {{ count }}<small> / {{ summary | roundFloat }}</small>
                        </div>
                    </a>
                </template>

                <template #sidebar-content="{ height }">
                    <SidebarLayout>
                        <template #header>
                            <SidebarNavbar
                                @search="q = $event"
                                @exportExcel="onExportExcel"
                            />
                        </template>
                        <template #content>
                            <Details :data="filteredInnerData" :height="height" class="p-2" />
                        </template>
                    </SidebarLayout>
                </template>
            </Sidebar>
            <div v-else>
                {{ count }}
            </div>
        </template>
    </MetricLayout>
</template>

<style scoped lang="scss">

</style>
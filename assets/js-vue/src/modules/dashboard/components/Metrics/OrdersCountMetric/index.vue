<script>
import { defineComponent } from 'vue'
import MetricLayout from "../MetricLayout.vue"
import Sidebar from '@/components/base/Sidebar.vue'
import BaseMetric from '../BaseMetric.js'
import Details from './Details.vue'

import {
    getProductionPendingDetails,
    getProductionFinishedDetails,
} from "../../../repository";

export default defineComponent({
    name: 'PendingOrdersMetric',
    extends: BaseMetric,
    props: {
        status: {
            type: String,
            validator: item => ['orders_pending', 'orders_finished'].includes(item),
        }
    },
    components: {
        MetricLayout, Sidebar, Details,
    },
    computed: {
        canSeeProduction() {
            return this.$user.can(this.$privilages.CAN_PRODUCTION);
        },
        count() {
            if (!this.data) {
                return 0
            }

            return Array.isArray(this.data[this.status]) ? this.data[this.status][0].count : 0;
        },
        summary() {
            if (!this.data) {
                return 0
            }
            return Array.isArray(this.data[this.status]) ? this.data[this.status][0].factors_summary : 0;
        }
    },

    methods: {
        fetchDetails(callback) {
            this.isFetchingDetails = true
            const promise = this.status === 'orders_pending'
                ? getProductionPendingDetails(null, this.filters?.dateEnd)
                : getProductionFinishedDetails(this.filters?.dateStart, this.filters?.dateEnd);

            return promise
                .then(({data}) => {
                    this.detailsData = data;
                    callback()
                })
                .finally(() => this.isFetchingDetails = false)
        }
    },

    data: () => ({
        isFetchingDetails: false,
        detailsData: []
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
                    <Details :data="detailsData" :height="height" class="p-2" />
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
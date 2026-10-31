<script>
import AgreementLineShowcaseList from "@/components/base/Showcase/AgreementLineShowcaseList.vue";

import {getLocalDate, orderDisplayNumber} from '@/helpers'
import {deburr} from "lodash";

export default {
    name: "CapacitySidebar",

    props: {
        data: {
            type: Object,
            default: () => ({}),
        },
        weekData: {
            type: Object,
            default: null,
        },
        // set by an owner that searches from the drawer header; null = the list's own search box
        search: {
            type: String,
            default: null,
        },
    },

    components: {
        AgreementLineShowcaseList,
    },

    mounted() {
        if (this.weekData) {
            const from = this.formatDate(this.weekData.dateStart)
            const to = this.formatDate(this.weekData.dateEnd)
            this.$emit('set-title', `${this.$t('dashboard.weeklyCapacityMetric')}: ${from} – ${to}`)
        } else if (this.data?.arg?.date) {
            this.$emit('set-title', `${this.$t('schedule.weeklyOrdersInCapacity')} - ${getLocalDate(this.data?.arg?.date)}`)
        }
    },

    methods: {
        formatDate(dateStr) {
            if (!dateStr) return ''
            const d = new Date(dateStr)
            return d.toLocaleDateString('pl-PL', { day: '2-digit', month: '2-digit' })
        }
    },

    computed: {
        capacityData() {
            if (this.weekData) {
                return this.weekData
            }
            return (this.data?.events?.capacity || [])[0]
        },

        summary() {
            const { capacity, capacityBurned } = this.capacityData || {}
            const roundFloat = this.$options.filters.roundFloat

            return [
                { label: this.$t('schedule.weekCapacity'), value: roundFloat(capacity) },
                {
                    label: this.$t('schedule.capacityBurned'),
                    value: roundFloat(capacityBurned),
                    hint: capacity ? `${Math.round((capacityBurned / capacity) * 100)}%` : null,
                },
            ]
        },

        filteredSelectedData() {
            let data = this.capacityData?.agreementLines || []
            const lines = Object.values(data)

            const term = this.search !== null ? this.search : this.q
            if (!term) {
                return lines
            }

            const searchTerm = deburr(term).toLowerCase()

            return lines.filter(item => {
                const haystack = [
                    item.q || `${item.customerName} ${item.productName} ${item.orderNumber}`,
                    orderDisplayNumber(item.orderNumber, item.internalNumber),
                ].join(' ')

                return deburr(haystack).toLowerCase().includes(searchTerm)
            })
        },
    },
    data: () => ({ q: '' })
}
</script>

<template>
    <AgreementLineShowcaseList
        :lines="filteredSelectedData"
        :summary="summary"
        :searchable="search === null"
        @search="q = $event"
    />
</template>

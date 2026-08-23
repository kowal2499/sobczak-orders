<script>
import { defineComponent } from 'vue'
import { MONTHS } from '@/services/datesService'
import TablePlus from '@/components/base/TablePlus.vue'
import ActivityLogList from '@/modules/agreement/components/ActivityLogList.vue'
import { fetchPeriod, fetchPeriodLogs, fetchPeriods } from './repository/bonusSettlementRepository'

export default defineComponent({
    name: 'BonusSettlement',
    components: { ActivityLogList, TablePlus },
    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('bonus_settlement.breadcrumb.home') },
                { label: this.$t('bonus_settlement.breadcrumb.bonuses') },
                { label: this.$t('bonus_settlement.breadcrumb.current') },
            ]
        },
        periodOptions() {
            return this.periods.map(period => ({
                value: period.id,
                text: `${this.periodLabel(period)} - ${this.$t(`bonus_settlement.status.${period.status}`)}`,
            }))
        },
        tableHeaders() {
            return [
                { name: this.$t('bonus_settlement.col.employee') },
                { name: this.$t('bonus_settlement.col.department') },
                { name: this.$t('bonus_settlement.col.calculated'), wrapperClass: 'text-right' },
                { name: this.$t('bonus_settlement.col.granted'), wrapperClass: 'text-right' },
                { name: this.$t('bonus_settlement.col.total'), wrapperClass: 'text-right' },
            ].map(item => ({ items: [item] }))
        },
        /**
         * Premię wypłaca się człowiekowi, więc działy są podwierszami pracownika, a "Razem"
         * sumuje na poziomie osoby. Backend oddaje wiersze posortowane po pracowniku i dziale,
         * więc wystarczy jedno przejście.
         */
        groups() {
            const groups = []
            let current = null

            for (const entry of this.period ? this.period.entries : []) {
                if (!current || current.userId !== entry.userId) {
                    current = { userId: entry.userId, userLabel: entry.userLabel, rows: [], total: 0 }
                    groups.push(current)
                }
                current.rows.push(entry)
                current.total += entry.factorsEffective
            }

            return groups
        },
        logsFetcher() {
            const id = this.selectedId
            return params => fetchPeriodLogs(id, params)
        },
    },
    methods: {
        periodLabel(period) {
            const month = MONTHS.find(m => m.number === period.month - 1)
            return `${month ? this.$t(month.name) : period.month} ${period.year}`
        },
        fmt(value) {
            if (value === null || value === undefined) {
                return '-'
            }
            return new Intl.NumberFormat(this.$i18n.locale === 'en' ? 'en-US' : 'pl-PL', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value)
        },
        async loadPeriods() {
            this.loading = true
            try {
                const { data } = await fetchPeriods()
                this.periods = data.data
                this.selectedId = this.periods.length ? this.periods[0].id : null
            } catch {
                this.$flash.danger(this.$t('bonus_settlement.error.load_periods'))
            } finally {
                this.loading = false
            }
        },
        async loadPeriod(id) {
            if (!id) {
                this.period = null
                return
            }
            this.loading = true
            try {
                const { data } = await fetchPeriod(id)
                this.period = data.data
            } catch {
                this.period = null
                this.$flash.danger(this.$t('bonus_settlement.error.load_period'))
            } finally {
                this.loading = false
            }
        },
    },
    watch: {
        selectedId(id) {
            this.loadPeriod(id)
        },
    },
    mounted() {
        this.loadPeriods()
    },
    data: () => ({
        periods: [],
        selectedId: null,
        period: null,
        loading: false,
    }),
})
</script>

<template>
    <div>
        <SectionBlockTitle block :title="$t('bonus_settlement.title')" :breadcrumbs="breadcrumbs">
            <template #filters>
                <b-form inline>
                    <b-form-select
                        v-if="periods.length"
                        v-model="selectedId"
                        :options="periodOptions"
                        class="mr-3"
                    />
                    <b-spinner v-if="loading" small variant="secondary" />
                </b-form>
            </template>
        </SectionBlockTitle>

        <SectionBlock v-if="period" class="section-gap period-header">
            <div class="period-header__line">
                <span class="badge" :class="period.status === 'OPEN' ? 'badge-success' : 'badge-secondary'">
                    {{ $t(`bonus_settlement.status.${period.status}`) }}
                </span>
                <span class="text-muted">
                    {{ $t('bonus_settlement.header.tolerance', { days: period.toleranceDays }) }}
                </span>
            </div>

            <div class="period-header__line mt-2">
                <span class="text-muted">
                    {{ $t('bonus_settlement.header.calculated_at') }}:
                    <template v-if="period.calculatedAt">{{ period.calculatedAt }}</template>
                    <template v-else>{{ $t('bonus_settlement.header.never_calculated') }}</template>
                </span>
                <span class="font-weight-bold">
                    {{ $t('bonus_settlement.header.total_granted') }}: {{ fmt(period.totalEffective) }}
                </span>
            </div>

            <div v-if="period.status === 'CLOSED'" class="period-header__line mt-2 text-muted">
                {{ $t('bonus_settlement.header.closed_by', {
                    date: period.closedAt,
                    user: period.closedByLabel || $t('bonus_settlement.header.unknown_user'),
                }) }}
            </div>
        </SectionBlock>

        <SectionBlock v-if="period && groups.length" class="section-gap">
            <TablePlus :headers="tableHeaders" :loading="loading" sticky-header>
                <template v-for="group in groups">
                    <tr
                        v-for="(row, index) in group.rows"
                        :key="row.id"
                        :class="{ 'group-start': index === 0 }"
                    >
                        <td v-if="index === 0" :rowspan="group.rows.length" class="align-middle font-weight-bold">
                            {{ group.userLabel }}
                        </td>
                        <td>{{ row.departmentLabel }}</td>
                        <td class="text-right numeric">{{ fmt(row.factorsCalculated) }}</td>
                        <td class="text-right numeric">{{ fmt(row.factorsEffective) }}</td>
                        <td
                            v-if="index === 0"
                            :rowspan="group.rows.length"
                            class="align-middle text-right numeric font-weight-bold"
                        >
                            {{ fmt(group.total) }}
                        </td>
                    </tr>
                </template>
            </TablePlus>
        </SectionBlock>

        <SectionBlock v-else-if="period" class="section-gap text-muted">
            {{ $t('bonus_settlement.no_entries') }}
        </SectionBlock>

        <SectionBlock v-else-if="!loading" class="section-gap text-muted">
            {{ $t('bonus_settlement.no_periods') }}
        </SectionBlock>

        <SectionBlock v-if="period" class="section-gap">
            <h2 class="logs-title">{{ $t('bonus_settlement.logs.title') }}</h2>
            <!-- Klucz na okresie: zmiana wyboru ma przeładować dziennik, a lista pobiera dane przy montowaniu. -->
            <ActivityLogList
                :key="`logs-${period.id}`"
                :fetcher="logsFetcher"
                :page-size="10"
                load-on-mount
            />
        </SectionBlock>
    </div>
</template>

<style scoped lang="scss">
.section-gap {
    margin-top: 2rem;
}

.period-header {
    &__line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        font-size: 0.875rem;
    }
}

// Kreska tylko na styku grup - wewnątrz pracownika wiersze mają się czytać jako jeden blok.
.group-start td {
    border-top: 2px solid #dee2e6;
}

// Cyfry o równej szerokości bez zmiany kroju - kolumny mają się układać w słupek,
// ale liczby zostają w foncie interfejsu.
.numeric {
    font-variant-numeric: tabular-nums;
}

.logs-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--colorPrimary);
    margin-bottom: 1rem;
}
</style>

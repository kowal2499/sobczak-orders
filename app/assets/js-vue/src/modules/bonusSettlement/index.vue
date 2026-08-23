<script>
import { defineComponent } from 'vue'
import { MONTHS } from '@/services/datesService'
import TablePlus from '@/components/base/TablePlus.vue'
import ConfirmationModal from '@/components/base/ConfirmationModal.vue'
import ActivityLogList from '@/modules/agreement/components/ActivityLogList.vue'
import ExcelExport from '@/services/ExcelExport/ExcelExport'
import AdjustmentCell from './components/AdjustmentCell.vue'
import {
    closePeriod,
    createPeriod,
    fetchPeriod,
    fetchPeriodLogs,
    fetchPeriods,
    recalculatePeriod,
    reopenPeriod,
    resetPeriodAdjustments,
} from './repository/bonusSettlementRepository'

const FIRST_YEAR = 2024

export default defineComponent({
    name: 'BonusSettlement',
    components: { ActivityLogList, AdjustmentCell, ConfirmationModal, TablePlus },
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
        canManage() {
            return this.$user.can('bonus-settlement.manage')
        },
        isClosed() {
            return this.period !== null && this.period.status === 'CLOSED'
        },
        // akcje zmieniające wsad znikają w zamkniętym okresie - zostaje ponowne otwarcie
        canEdit() {
            return this.canManage && this.period !== null && !this.isClosed
        },
        confirmQuestion() {
            return this.pendingAction === null
                ? ''
                : this.$t(`bonus_settlement.confirm.${this.pendingAction}`)
        },
        yearOptions() {
            const lastYear = new Date().getFullYear() + 1
            const years = []
            for (let year = lastYear; year >= FIRST_YEAR; year--) {
                years.push({ value: year, text: year })
            }
            return years
        },
        monthOptions() {
            return MONTHS.map(month => ({ value: month.number + 1, text: this.$t(month.name) }))
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
        /**
         * Odsyłacz do źródła wartości: pulpit z kaflem "Ukończone zadania produkcyjne (w terminie)"
         * ustawiony na miesiąc okresu. Kafel pokazuje wszystkie działy naraz, więc działu nie da
         * się podać w adresie.
         */
        sourceReportHref() {
            return `/?year=${this.period.year}&month=${this.period.month - 1}`
        },
        ask(action) {
            this.pendingAction = action
        },
        cancel() {
            this.pendingAction = null
        },
        async runPendingAction() {
            const action = this.pendingAction
            this.busy = true
            try {
                if (action === 'create') {
                    await createPeriod(this.createForm.year, this.createForm.month)
                    await this.loadPeriods()
                } else {
                    await this.callAction(action)
                    await this.loadPeriod(this.selectedId)
                }
                this.pendingAction = null
                this.refreshLogs()
                this.$flash.success(this.$t('bonus_settlement.action_done'))
            } catch (error) {
                this.$flash.danger(
                    error?.response?.data?.error || this.$t('bonus_settlement.error.action')
                )
            } finally {
                this.busy = false
            }
        },
        callAction(action) {
            const id = this.selectedId
            switch (action) {
                case 'recalculate':
                    return recalculatePeriod(id)
                case 'reset':
                    return resetPeriodAdjustments(id)
                case 'close':
                    return closePeriod(id)
                case 'reopen':
                    return reopenPeriod(id)
                default:
                    return Promise.reject(new Error(`Nieznana akcja: ${action}`))
            }
        },
        async onAdjustmentSaved() {
            await this.loadPeriod(this.selectedId)
            this.refreshLogs()
        },
        // dziennik pobiera dane przy montowaniu, więc przeładowanie idzie przez klucz
        refreshLogs() {
            this.logsRefresh += 1
        },
        /**
         * Eksport po stronie klienta, wzorem mierników pulpitu. Arkusz jest płaski - jeden wiersz
         * na parę pracownik-dział - bo scalone komórki z ekranu psują sortowanie i filtry w Excelu.
         */
        exportExcel() {
            const label = this.periodLabel(this.period)
            const fields = [
                { title: this.$t('bonus_settlement.col.employee'), getValue: row => row.userLabel },
                { title: this.$t('bonus_settlement.col.department'), getValue: row => row.departmentLabel },
                { title: this.$t('bonus_settlement.col.calculated'), getValue: row => row.factorsCalculated },
                { title: this.$t('bonus_settlement.col.granted'), getValue: row => row.factorsEffective },
                { title: this.$t('bonus_settlement.adjustment.note'), getValue: row => row.note || '' },
            ]

            const excel = new ExcelExport()
            const sheet = excel.addWorksheet(this.$t('bonus_settlement.title'), fields)

            sheet.worksheet.spliceRows(1, 0,
                [label],
                [this.$t('bonus_settlement.header.tolerance', { days: this.period.toleranceDays })],
                []
            )

            this.period.entries.forEach(entry => sheet.addData(entry))

            return excel.save(`${this.$t('bonus_settlement.title')} ${label}.xlsx`)
                .catch(() => this.$flash.danger(this.$t('bonus_settlement.error.export')))
                .finally(() => excel.clear())
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
    data() {
        const today = new Date()

        return {
            periods: [],
            selectedId: null,
            period: null,
            loading: false,
            pendingAction: null,
            busy: false,
            logsRefresh: 0,
            createForm: { year: today.getFullYear(), month: today.getMonth() + 1 },
        }
    },
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

                    <button
                        v-if="canManage"
                        type="button"
                        class="btn btn-primary btn-sm ml-auto"
                        @click="ask('create')"
                    >
                        {{ $t('bonus_settlement.action.create') }}
                    </button>
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

            <div v-if="isClosed" class="period-header__line mt-2 text-muted">
                {{ $t('bonus_settlement.header.closed_by', {
                    date: period.closedAt,
                    user: period.closedByLabel || $t('bonus_settlement.header.unknown_user'),
                }) }}
            </div>

            <div class="period-header__actions mt-3">
                <template v-if="canEdit">
                    <button type="button" class="btn btn-primary btn-sm" @click="ask('recalculate')">
                        {{ $t('bonus_settlement.action.recalculate') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="ask('reset')">
                        {{ $t('bonus_settlement.action.reset') }}
                    </button>
                </template>

                <!-- Eksport niczego nie zmienia, więc jest bez potwierdzenia i w każdym statusie. -->
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    :disabled="!period.entries.length"
                    @click="exportExcel"
                >
                    {{ $t('bonus_settlement.action.export') }}
                </button>

                <button
                    v-if="canEdit"
                    type="button"
                    class="btn btn-outline-danger btn-sm ml-auto"
                    @click="ask('close')"
                >
                    {{ $t('bonus_settlement.action.close') }}
                </button>
                <button
                    v-if="canManage && isClosed"
                    type="button"
                    class="btn btn-outline-secondary btn-sm ml-auto"
                    @click="ask('reopen')"
                >
                    {{ $t('bonus_settlement.action.reopen') }}
                </button>
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
                        <td>
                            <a
                                :href="sourceReportHref()"
                                v-b-tooltip.hover
                                :title="$t('bonus_settlement.source_report')"
                            >{{ row.departmentLabel }}</a>
                        </td>
                        <td class="text-right numeric">{{ fmt(row.factorsCalculated) }}</td>
                        <td class="text-right numeric">
                            <AdjustmentCell
                                :entry="row"
                                :readonly="!canEdit"
                                @saved="onAdjustmentSaved"
                            >{{ fmt(row.factorsEffective) }}</AdjustmentCell>
                        </td>
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
                :key="`logs-${period.id}-${logsRefresh}`"
                :fetcher="logsFetcher"
                :page-size="10"
                load-on-mount
            />
        </SectionBlock>

        <ConfirmationModal
            :show="pendingAction !== null"
            :busy="busy"
            @answerYes="runPendingAction"
            @closeModal="cancel"
        >
            <p>{{ confirmQuestion }}</p>

            <b-form v-if="pendingAction === 'create'" inline>
                <b-form-select v-model="createForm.month" :options="monthOptions" class="mr-2" />
                <b-form-select v-model="createForm.year" :options="yearOptions" />
            </b-form>
        </ConfirmationModal>
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

    &__actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
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

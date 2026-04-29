<script>
import { defineComponent } from 'vue'
import { DEPARTMENTS } from '@/helpers'
import { MONTHS, dateToString, firstDay, lastDay } from '@/services/datesService'
import { getDepartmentsCapacity, getProductionTasksCompletionSummary } from '@/modules/dashboard/repository'
import Sidebar from '@/components/base/Sidebar.vue'
import SidebarLayout from '@/components/layout/SidebarLayout.vue'
import DetailsDepartment from '@/modules/dashboard/components/Metrics/ProductionMetric/components/DetailsDepartment.vue'

const START_YEAR = 2018

function r2(val) {
    return Math.round(val * 100) / 100
}

export default defineComponent({
    name: 'DifferencesReport',
    components: { Sidebar, SidebarLayout, DetailsDepartment },
    computed: {
        yearsOptions() {
            const currentYear = new Date().getFullYear()
            const years = Array.from({ length: currentYear - START_YEAR + 2 }, (_, i) => i + START_YEAR).reverse()
            return [
                { value: null, text: this.$t('dashboard.year_placeholder'), disabled: true },
                ...years.map(y => ({ value: y, text: y }))
            ]
        },
        monthsOptions() {
            return [
                { value: null, text: this.$t('dashboard.month_placeholder'), disabled: true },
                ...MONTHS.map(m => ({ value: m.number, text: this.$t(m.name) }))
            ]
        },
        dateRangeStart() {
            return this.filters.year !== null && this.filters.month !== null
                ? dateToString(firstDay(this.filters.year, this.filters.month))
                : null
        },
        dateRangeEnd() {
            return this.filters.year !== null && this.filters.month !== null
                ? dateToString(lastDay(this.filters.year, this.filters.month))
                : null
        },
        rows() {
            if (!this.odp || !this.uzp || !this.dateRangeStart || !this.dateRangeEnd) {
                return []
            }
            const ds = this.dateRangeStart
            const de = this.dateRangeEnd

            const odpByDept = {}
            for (const r of this.odp) {
                if (!odpByDept[r.departmentSlug]) odpByDept[r.departmentSlug] = []
                odpByDept[r.departmentSlug].push(r)
            }
            const uzpByDept = {}
            for (const r of this.uzp) {
                if (!uzpByDept[r.departmentSlug]) uzpByDept[r.departmentSlug] = []
                uzpByDept[r.departmentSlug].push(r)
            }

            const allSlugs = new Set([...Object.keys(odpByDept), ...Object.keys(uzpByDept)])

            return DEPARTMENTS
                .filter(d => allSlugs.has(d.slug))
                .map(d => ({
                    name: d.name,
                    slug: d.slug,
                    ...this.computeStats(odpByDept[d.slug] || [], uzpByDept[d.slug] || [], ds, de)
                }))
        },
        mappedSidebarRecords() {
            return this.sidebarRecords.map(r => this.toDetailRecord(r))
        },
    },
    methods: {
        computeStats(odpRecords, uzpRecords, ds, de) {
            let odpTotal = 0, sharedOdp = 0, incomplete = 0, completedOutside = 0
            const incompleteRecs = [], completedOutsideRecs = []

            for (const r of odpRecords) {
                const val = r.factors?.factor ?? 0
                odpTotal += val
                const completedAt = r.completedAt?.slice(0, 10)
                if (!completedAt) {
                    incomplete += val
                    incompleteRecs.push(r)
                } else if (completedAt >= ds && completedAt <= de) {
                    sharedOdp += val
                } else {
                    completedOutside += val
                    completedOutsideRecs.push(r)
                }
            }

            let uzpTotal = 0, sharedUzp = 0, delayed = 0, accelerated = 0
            const sharedUzpRecs = [], delayedRecs = [], acceleratedRecs = []

            for (const r of uzpRecords) {
                const val = r.factors?.factor ?? 0
                uzpTotal += val
                const dateEnd = r.dateEnd?.slice(0, 10)
                if (!dateEnd || dateEnd > de) {
                    accelerated += val
                    acceleratedRecs.push(r)
                } else if (dateEnd < ds) {
                    delayed += val
                    delayedRecs.push(r)
                } else {
                    sharedUzp += val
                    const hasBonus = (r.factors?.factorsStack || []).some(f => f.source === 'factor_adjustment_bonus' && f.value !== 0)
                    if (hasBonus) sharedUzpRecs.push(r)
                }
            }

            const correction = r2(sharedUzp - sharedOdp)
            const delta = r2(uzpTotal - odpTotal)
            const check = r2(correction + delayed + accelerated - incomplete - completedOutside)

            return {
                odpTotal: r2(odpTotal),
                uzpTotal: r2(uzpTotal),
                delta,
                correction,
                incomplete: r2(incomplete),
                completedOutside: r2(completedOutside),
                delayed: r2(delayed),
                accelerated: r2(accelerated),
                check,
                balanced: Math.abs(delta - check) < 0.01,
                _correctionRecs: sharedUzpRecs,
                _incompleteRecs: incompleteRecs,
                _completedOutsideRecs: completedOutsideRecs,
                _delayedRecs: delayedRecs,
                _acceleratedRecs: acceleratedRecs,
            }
        },
        toDetailRecord(r) {
            return {
                id: r.agreementLine?.id,
                customerName: r.customer?.name,
                productName: r.agreementLine?.productName,
                orderNumber: r.agreement?.orderNumber,
                data: {
                    factor: r.factors?.factor,
                    factorsStack: r.factors?.factorsStack || [],
                    production: {
                        status: r.status,
                        dateStart: r.dateStart,
                        dateEnd: r.dateEnd,
                        departmentSlug: r.departmentSlug,
                    },
                },
            }
        },
        openSidebar(records, deptName, groupLabel) {
            this.sidebarRecords = records
            this.sidebarTitle = `${deptName} — ${groupLabel}`
            this.sidebarVisible = true
        },
        fmt(val) {
            return parseFloat(val.toFixed(2)).toString()
        },
        fmtDelta(val) {
            const s = parseFloat(val.toFixed(2)).toString()
            return val > 0.005 ? `+${s}` : s
        },
        deltaClass(val) {
            if (val > 0.005) return 'text-success'
            if (val < -0.005) return 'text-danger'
            return 'text-muted'
        },
        async fetchData() {
            if (!this.dateRangeStart || !this.dateRangeEnd) return
            this.busy = true
            this.odp = null
            this.uzp = null
            try {
                const [odpRes, uzpRes] = await Promise.all([
                    getDepartmentsCapacity(this.dateRangeStart, this.dateRangeEnd),
                    getProductionTasksCompletionSummary(this.dateRangeStart, this.dateRangeEnd),
                ])
                this.odp = Array.isArray(odpRes.data) ? odpRes.data : []
                this.uzp = Array.isArray(uzpRes.data) ? uzpRes.data : []
            } catch {
                this.odp = []
                this.uzp = []
            } finally {
                this.busy = false
            }
        },
    },
    watch: {
        filters: {
            deep: true,
            handler() { this.fetchData() },
        },
    },
    mounted() {
        const today = new Date()
        this.filters.year = today.getFullYear()
        this.filters.month = today.getMonth()
    },
    data: () => ({
        filters: { year: null, month: null },
        busy: false,
        odp: null,
        uzp: null,
        sidebarVisible: false,
        sidebarTitle: '',
        sidebarRecords: [],
    }),
})
</script>

<template>
    <div>
        <div class="section-block d-flex align-items-center justify-content-between">
            <span class="report-title">{{ $t('differences_report.title') }}</span>
            <b-form inline>
                <b-form-select v-model="filters.year" :options="yearsOptions" class="mr-3" />
                <b-form-select v-model="filters.month" :options="monthsOptions" class="mr-3" />
                <b-spinner v-if="busy" small variant="secondary" />
            </b-form>
        </div>

        <div v-if="!busy && rows.length" class="section-block section-gap" style="padding: 2rem">
            <div class="table-responsive">
                <table class="table table-sm table-hover diff-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th rowspan="2" class="align-middle">{{ $t('differences_report.col.department') }}</th>
                            <th class="text-center border-right-bold" colspan="3">{{ $t('differences_report.col.totals') }}</th>
                            <th class="text-center">{{ $t('differences_report.col.correction_header') }}</th>
                            <th class="text-center" colspan="2">{{ $t('differences_report.col.odp_groups') }}</th>
                            <th class="text-center" colspan="2">{{ $t('differences_report.col.uzp_groups') }}</th>
                            <th rowspan="2" class="align-middle text-center">{{ $t('differences_report.col.verify') }}</th>
                        </tr>
                        <tr>
                            <th class="text-right" v-b-tooltip.hover :title="$t('differences_report.col.odp_tooltip')">{{ $t('differences_report.col.odp') }}</th>
                            <th class="text-right" v-b-tooltip.hover :title="$t('differences_report.col.uzp_tooltip')">{{ $t('differences_report.col.uzp') }}</th>
                            <th class="text-right border-right-bold">{{ $t('differences_report.col.delta') }}</th>
                            <th class="text-right">
                                <span v-b-tooltip.hover :title="$t('differences_report.col.correction_tooltip')">{{ $t('differences_report.col.correction') }}</span>
                            </th>
                            <th class="text-right">
                                <span v-b-tooltip.hover :title="$t('differences_report.col.incomplete_tooltip')">{{ $t('differences_report.col.incomplete') }}</span>
                            </th>
                            <th class="text-right">
                                <span v-b-tooltip.hover :title="$t('differences_report.col.completed_outside_tooltip')">{{ $t('differences_report.col.completed_outside') }}</span>
                            </th>
                            <th class="text-right">
                                <span v-b-tooltip.hover :title="$t('differences_report.col.delayed_tooltip')">{{ $t('differences_report.col.delayed') }}</span>
                            </th>
                            <th class="text-right">
                                <span v-b-tooltip.hover :title="$t('differences_report.col.accelerated_tooltip')">{{ $t('differences_report.col.accelerated') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.slug">
                            <td class="font-weight-bold">{{ row.name }}</td>
                            <td class="text-right text-monospace">{{ fmt(row.odpTotal) }}</td>
                            <td class="text-right text-monospace">{{ fmt(row.uzpTotal) }}</td>
                            <td class="text-right text-monospace font-weight-bold" :class="deltaClass(row.delta)">
                                {{ fmtDelta(row.delta) }}
                            </td>
                            <td class="text-right text-monospace">
                                <span
                                    v-if="row.correction !== 0"
                                    class="group-link"
                                    :class="deltaClass(row.correction)"
                                    @click="openSidebar(row._correctionRecs, row.name, $t('differences_report.col.correction'))"
                                >{{ fmtDelta(row.correction) }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <td class="text-right text-monospace">
                                <span
                                    v-if="row.incomplete"
                                    class="group-link text-danger"
                                    @click="openSidebar(row._incompleteRecs, row.name, $t('differences_report.col.incomplete'))"
                                >−{{ fmt(row.incomplete) }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <td class="text-right text-monospace">
                                <span
                                    v-if="row.completedOutside"
                                    class="group-link text-danger"
                                    @click="openSidebar(row._completedOutsideRecs, row.name, $t('differences_report.col.completed_outside'))"
                                >−{{ fmt(row.completedOutside) }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <td class="text-right text-monospace">
                                <span
                                    v-if="row.delayed"
                                    class="group-link text-success"
                                    @click="openSidebar(row._delayedRecs, row.name, $t('differences_report.col.delayed'))"
                                >+{{ fmt(row.delayed) }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <td class="text-right text-monospace">
                                <span
                                    v-if="row.accelerated"
                                    class="group-link text-success"
                                    @click="openSidebar(row._acceleratedRecs, row.name, $t('differences_report.col.accelerated'))"
                                >+{{ fmt(row.accelerated) }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <td class="text-center">
                                <font-awesome-icon v-if="row.balanced" icon="check" class="text-success" />
                                <font-awesome-icon v-else icon="times" class="text-danger" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else-if="!busy && odp !== null && !rows.length" class="section-block section-gap text-muted">
            {{ $t('differences_report.no_data') }}
        </div>

        <div v-if="!busy && rows.length" class="legend section-gap">
            <div class="legend__row">
                <strong>{{ $t('differences_report.col.odp') }}</strong> — {{ $t('differences_report.legend.odp') }} &nbsp;·&nbsp;
                <strong>{{ $t('differences_report.col.uzp') }}</strong> — {{ $t('differences_report.legend.uzp') }} &nbsp;·&nbsp;
                <strong>{{ $t('differences_report.col.delta') }}</strong> — {{ $t('differences_report.legend.delta') }}
            </div>
            <div class="legend__row mt-1">
                <strong>{{ $t('differences_report.legend.formula') }}</strong>
                {{ $t('differences_report.legend.verify_hint') }}
            </div>
        </div>

        <Sidebar
            :title="sidebarTitle"
            v-model="sidebarVisible"
            sidebar-class="size-100 size-lg-50"
        >
            <template #sidebar-content>
                <SidebarLayout>
                    <template #content>
                        <DetailsDepartment
                            v-for="(record, i) in mappedSidebarRecords"
                            :key="`sidebar-${i}`"
                            :record="record"
                        />
                    </template>
                </SidebarLayout>
            </template>
        </Sidebar>
    </div>
</template>

<style scoped lang="scss">
.report-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--colorPrimary);
}

.section-block {
    background-color: #fff;
    padding: 1rem;
    border-radius: 1rem;
    border: 1px solid #ddd;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.section-gap {
    margin-top: 2rem;
}

.legend {
    background-color: #eef2f7;
    border: 1px solid #cdd8e8;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.83rem;
    color: var(--colorPrimary);

    &__row {
        line-height: 1.5;
    }
}

.diff-table {
    font-size: 0.875rem;

    th, td {
        white-space: nowrap;
        vertical-align: middle;
    }

    th {
        color: var(--colorPrimary);
    }

    .border-right-bold {
        border-right: 2px solid #adb5bd !important;
    }

    .group-link {
        cursor: pointer;
        text-decoration: underline;
        text-decoration-style: dotted;

        &:hover {
            opacity: 0.75;
        }
    }
}
</style>

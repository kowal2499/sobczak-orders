<template>
    <div>
        <SectionBlock class="d-flex align-items-center justify-content-between flex-wrap">
            <SectionBlockTitle :title="$t('dashboard.title')" />

            <div class="d-flex align-items-center">
                <b-form inline class="mb-0 mr-3">
                    <b-form-select v-model="filters.year" :options="yearsOptions" class="mr-3" />
                    <b-form-select v-model="filters.month" :options="monthsOptions" />
                </b-form>

                <button
                    v-if="editMode"
                    class="btn btn-outline-secondary btn-sm mr-2 d-none d-md-inline-block"
                    type="button"
                    @click="resetLayout"
                >
                    <font-awesome-icon icon="undo" />
                    {{ $t('dashboard.layout.reset') }}
                </button>
                <button
                    class="btn btn-sm d-none d-md-inline-block"
                    :class="editMode ? 'btn-primary' : 'btn-outline-primary'"
                    type="button"
                    @click="editMode = !editMode"
                >
                    <font-awesome-icon icon="arrows-alt" />
                    {{ editMode ? $t('dashboard.layout.done') : $t('dashboard.layout.edit') }}
                </button>
            </div>
        </SectionBlock>

        <SectionBlock v-if="gridLayout.length" class="section-gap">
            <EditableGridLayout
                :layout="gridLayout"
                :editable="editMode"
                @update:layout="onLayoutUpdate"
            >
                <template #default="{ item }">
                    <div class="dashboard-widget" :class="{ 'dashboard-widget--hidden': editMode && !isVisible(item.i) }">
                        <button
                            v-if="editMode"
                            class="dashboard-widget__visibility-toggle btn btn-sm btn-light"
                            type="button"
                            :title="$t(isVisible(item.i) ? 'dashboard.layout.hide' : 'dashboard.layout.show')"
                            @click="toggleVisibility(item.i)"
                        >
                            <font-awesome-icon :icon="isVisible(item.i) ? 'eye-slash' : 'eye'" />
                        </button>
                        <component :is="widgetComponent(item.i)" v-bind="widgetProps(item.i)" />
                    </div>
                </template>
            </EditableGridLayout>
        </SectionBlock>
    </div>
</template>

<script>
import { debounce } from "lodash";
import EditableGridLayout from "../../components/base/EditableGridLayout";
import { MONTHS, dateToString, firstDay, lastDay } from "../../services/datesService";
import { WIDGETS, getAvailableWidgets, packDefaultLayout } from "./widgetRegistry";
import { getUserSetting, saveUserSetting } from "../userSettings/repository";

import {
    getAgreementLinesSummary,
    getProductionTasksCompletionSummary,
    getOldSummary, getDepartmentsCapacity, getWeeklyCapacity
} from "./repository";

const START_YEAR = 2018;
const LAYOUT_CONTEXT = 'dashboard.layout';

const DATA_SOURCES = [
    { id: 'src01', fetcher: getOldSummary, active: true },
    { id: 'src02', fetcher: getAgreementLinesSummary, grant: 'canDashboardMetricsView', active: true },
    { id: 'src03', fetcher: getProductionTasksCompletionSummary, grant: 'canDashboardMetricsView', active: true },
    { id: 'src04', fetcher: getDepartmentsCapacity, grant: 'reports.dashboard:capacity-utilization', active: true },
    { id: 'src05', fetcher: getWeeklyCapacity, grant: 'reports.dashboard:weekly-capacity', active: true },
]

export default {
    name: 'Dashboard3',

    components: {
        EditableGridLayout,
    },

    computed: {
        months: () => MONTHS,
        years() {
            const currentYear = new Date().getFullYear();
            const yearsRange = currentYear - START_YEAR + 2;
            return Array.from({length: yearsRange }, (item, idx) => idx + START_YEAR).reverse()
        },
        yearsOptions() {
            return [
                { value: null, text: this.$t('dashboard.year_placeholder'), disabled: true },
                ...this.years.map(y => ({ value: y, text: y }))
            ];
        },
        monthsOptions() {
            return [
                { value: null, text: this.$t('dashboard.month_placeholder'), disabled: true },
                ...MONTHS.map(m => ({ value: m.number, text: this.$t(m.name) }))
            ];
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
        availableWidgets() {
            return getAvailableWidgets(grant => this.$user.can(grant));
        },
        availableWidgetsByKey() {
            return this.availableWidgets.reduce((acc, widget) => {
                acc[widget.key] = widget;
                return acc;
            }, {});
        },
        gridLayout() {
            const items = this.editMode ? this.layoutItems : this.layoutItems.filter(item => item.visible);
            return items.map(item => ({ i: item.key, x: item.x, y: item.y, w: item.w, h: item.h }));
        },
    },

    created() {
        this.sourcesState = DATA_SOURCES.map(source => ({
            id: source.id,
            isBusy: false,
            error: null,
            data: null,
        })).reduce((acc, item) => {
            acc[item.id] = item
            return acc
        }, {})

        this.persistLayout = debounce(this.saveLayout, 500);
    },

    mounted() {
        const today = new Date();
        this.filters.year = today.getFullYear();
        this.filters.month = today.getMonth();

        this.loadLayout();
    },

    watch: {
        filters: {
            deep: true,
            handler() {
                DATA_SOURCES.forEach(source => {
                    if (source.grant && !this.$user.can(source.grant)) {
                        return;
                    }
                    if (!source.active) {
                        return;
                    }
                    this.sourcesState[source.id].isBusy = true;
                    this.sourcesState[source.id].error = null;
                    source.fetcher(this.dateRangeStart, this.dateRangeEnd)
                        .then(({data}) => {
                            this.sourcesState[source.id].data = data;
                        })
                        .catch((error) => {
                            this.sourcesState[source.id].error = error;
                        })
                        .finally(() => {
                            this.sourcesState[source.id].isBusy = false;
                        });
                })
            }
        }
    },


    methods: {
        widgetComponent(key) {
            return this.availableWidgetsByKey[key]?.component;
        },
        widgetProps(key) {
            const widget = this.availableWidgetsByKey[key];
            return widget ? widget.props(this) : {};
        },
        isVisible(key) {
            return !!this.layoutItems.find(item => item.key === key)?.visible;
        },
        async loadLayout() {
            const savedWidgets = await this.fetchSavedLayout();
            this.layoutItems = this.mergeLayout(savedWidgets);

            if (!savedWidgets) {
                this.saveLayout();
            }
        },
        async fetchSavedLayout() {
            try {
                // Endpoint wraps the stored payload: { data: { widgets: [...] } }
                const { data: body } = await getUserSetting(LAYOUT_CONTEXT);
                return body?.data?.widgets ?? null;
            } catch (error) {
                if (error.response?.status === 404) {
                    return null;
                }
                throw error;
            }
        },
        mergeLayout(savedWidgets) {
            const available = this.availableWidgets;

            if (!savedWidgets || !savedWidgets.length) {
                return packDefaultLayout(available);
            }

            const savedByKey = savedWidgets.reduce((acc, item) => {
                acc[item.key] = item;
                return acc;
            }, {});

            const kept = available
                .filter(widget => savedByKey[widget.key])
                .map(widget => ({ ...savedByKey[widget.key] }));

            const newWidgets = available.filter(widget => !savedByKey[widget.key]);

            if (!newWidgets.length) {
                return kept;
            }

            const maxY = kept.reduce((max, item) => Math.max(max, item.y + item.h), 0);
            const appended = packDefaultLayout(newWidgets, { startY: maxY });

            return [...kept, ...appended];
        },
        saveLayout() {
            saveUserSetting(LAYOUT_CONTEXT, { widgets: this.layoutItems });
        },
        onLayoutUpdate(newGridLayout) {
            const positionsByKey = newGridLayout.reduce((acc, item) => {
                acc[item.i] = item;
                return acc;
            }, {});

            this.layoutItems = this.layoutItems.map(item => {
                const updated = positionsByKey[item.key];
                return updated ? { ...item, x: updated.x, y: updated.y, w: updated.w, h: updated.h } : item;
            });

            this.persistLayout();
        },
        toggleVisibility(key) {
            this.layoutItems = this.layoutItems.map(item =>
                item.key === key ? { ...item, visible: !item.visible } : item
            );
            this.persistLayout();
        },
        resetLayout() {
            this.layoutItems = packDefaultLayout(this.availableWidgets);
            this.saveLayout();
            this.editMode = false;
        },
    },

    data: () => ({
        sourcesState: {},
        layoutItems: [],
        editMode: false,

        filters: {
            month: null,
            year: null
        },
    }),
}
</script>

<style scoped lang="scss">
.section-gap {
    margin-top: 2rem;
}

.dashboard-widget {
    position: relative;
    height: 100%;
    overflow: hidden;

    &--hidden {
        opacity: 0.4;
    }

    &__visibility-toggle {
        position: absolute;
        top: 4px;
        right: 4px;
        z-index: 10;
    }
}
</style>

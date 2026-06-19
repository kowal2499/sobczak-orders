import WorkingDaysMetric from "./components/Metrics/WorkingDaysMetric.vue";
import FactorsLimitMetric from "./components/Metrics/FactorsLimitMetric.vue";
import OrdersCountMetric from "./components/Metrics/ProductionMetric/OrdersCountMetric/index.vue";
import DepartmentsBonusMetric from "./components/Metrics/ProductionMetric/DepartmentsBonusMetric/index.vue";
import CompletionDateMetric from "./components/Metrics/CompletionDateMetric.vue";
import CapacityMetric from "./components/Metrics/ProductionMetric/CapacityMetric/index.vue";
import WeeklyCapacityMetric from "./components/Metrics/WeeklyCapacityMetric/index.vue";
import PRIVILEGES from "../../definitions/userRoles";

const GRID_COLUMNS = 12;

/**
 * Each widget's default reading-order position (replaces the old hardcoded
 * row/col markup) and size. `grant`, when set, must be held by the current
 * user (this.$user.can(grant)) for the widget to be available at all.
 */
export const WIDGETS = [
    {
        key: "working_days",
        component: WorkingDaysMetric,
        order: 1,
        defaultSize: { w: 4, h: 2 },
        grant: null,
        props: ctx => ({ isBusy: ctx.sourcesState.src01.isBusy, data: ctx.sourcesState.src01.data }),
    },
    {
        key: "factors_limit",
        component: FactorsLimitMetric,
        order: 2,
        defaultSize: { w: 4, h: 2 },
        grant: null,
        props: ctx => ({ isBusy: ctx.sourcesState.src01.isBusy, data: ctx.sourcesState.src01.data }),
    },
    {
        key: "weekly_capacity",
        component: WeeklyCapacityMetric,
        order: 3,
        defaultSize: { w: 4, h: 4 },
        grant: "reports.dashboard:weekly-capacity",
        props: ctx => ({
            isBusy: ctx.sourcesState.src05.isBusy,
            data: ctx.sourcesState.src05.data,
            dateStart: ctx.dateRangeStart,
            dateEnd: ctx.dateRangeEnd,
        }),
    },
    {
        key: "orders_pending",
        component: OrdersCountMetric,
        order: 4,
        defaultSize: { w: 3, h: 2 },
        grant: PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW,
        props: ctx => ({
            isBusy: ctx.sourcesState.src02.isBusy,
            data: ctx.sourcesState.src02.data,
            filters: { dateStart: ctx.dateRangeStart, dateEnd: ctx.dateRangeEnd },
            status: "orders_pending",
            class: "border-left-primary",
        }),
    },
    {
        key: "orders_finished",
        component: OrdersCountMetric,
        order: 5,
        defaultSize: { w: 3, h: 2 },
        grant: PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW,
        props: ctx => ({
            isBusy: ctx.sourcesState.src02.isBusy,
            data: ctx.sourcesState.src02.data,
            filters: { dateStart: ctx.dateRangeStart, dateEnd: ctx.dateRangeEnd },
            status: "orders_finished",
            class: "border-left-success",
        }),
    },
    {
        key: "completion_date",
        component: CompletionDateMetric,
        order: 6,
        defaultSize: { w: 2, h: 2 },
        grant: null,
        props: ctx => ({ isBusy: ctx.sourcesState.src01.isBusy, data: ctx.sourcesState.src01.data }),
    },
    {
        key: "departments_bonus",
        component: DepartmentsBonusMetric,
        order: 7,
        defaultSize: { w: 4, h: 3 },
        grant: PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW,
        props: ctx => ({ isBusy: ctx.sourcesState.src03.isBusy, data: ctx.sourcesState.src03.data }),
    },
    {
        key: "capacity",
        component: CapacityMetric,
        order: 8,
        defaultSize: { w: 8, h: 3 },
        grant: "reports.dashboard:capacity-utilization",
        props: ctx => ({
            isBusy: ctx.sourcesState.src04.isBusy,
            data: ctx.sourcesState.src04.data,
            dateStart: ctx.dateRangeStart,
            dateEnd: ctx.dateRangeEnd,
        }),
    },
];

export function getAvailableWidgets(canFn) {
    return WIDGETS
        .filter(widget => !widget.grant || canFn(widget.grant))
        .sort((a, b) => a.order - b.order);
}

/**
 * Flows widgets shelf-style (CSS flex-wrap-like) into a fixed-column grid,
 * in the order given. Must only ever be called with an already
 * grant-filtered widget list — that way a missing grant simply shifts the
 * rest up/left instead of leaving a reserved empty cell.
 */
export function packDefaultLayout(widgets, { startY = 0 } = {}) {
    let cursorX = 0;
    let cursorY = startY;
    let rowHeight = 0;

    return widgets.map(widget => {
        const { w, h } = widget.defaultSize;

        if (cursorX + w > GRID_COLUMNS) {
            cursorX = 0;
            cursorY += rowHeight;
            rowHeight = 0;
        }

        const item = { key: widget.key, x: cursorX, y: cursorY, w, h, visible: true };

        cursorX += w;
        rowHeight = Math.max(rowHeight, h);

        return item;
    });
}

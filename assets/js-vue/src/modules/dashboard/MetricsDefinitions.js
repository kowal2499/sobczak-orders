import Metric, {
    METRIC_FACTORS_MONTHLY_LIMIT,
    METRIC_ORDERS_FINISHED,
    METRIC_ORDERS_PENDING,
    METRIC_WORKING_DAYS_COUNT,
    METRIC_DAY_OF_COMPLETION,
    METRIC_TASKS_COMPLETED,
    GROUP_ROW_1,
    GROUP_ROW_2,
    GROUP_ROW_3
} from "./model/DashboardMetric";
import PRIVILEGES from "../../definitions/userRoles";
import {getProductionFinishedDetails, getProductionPendingDetails} from "./repository";

export default [
    new Metric(
        METRIC_WORKING_DAYS_COUNT,
        `dashboard.${METRIC_WORKING_DAYS_COUNT}`,
        0,
        'border-left-primary',
        GROUP_ROW_1,
    ),
    new Metric(
        METRIC_FACTORS_MONTHLY_LIMIT,
        `dashboard.${METRIC_FACTORS_MONTHLY_LIMIT}`,
        0,
        'border-left-primary',
        GROUP_ROW_1
    ),
    new Metric(
        METRIC_ORDERS_PENDING,
        `dashboard.${METRIC_ORDERS_PENDING}`,
        0,
        'border-left-primary',
        GROUP_ROW_2,
        [PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW],
        () => import('./components/ProductionBadge'),
        (start, end) => getProductionPendingDetails(null, end)
    ),
    new Metric(
        METRIC_ORDERS_FINISHED,
        `dashboard.${METRIC_ORDERS_FINISHED}`,
        0,
        'border-left-success',
        GROUP_ROW_2,
        [PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW],
        () => import('./components/ProductionBadge'),
        (start, end) => getProductionFinishedDetails(start, end)
    ),
    new Metric(
        METRIC_DAY_OF_COMPLETION,
        `dashboard.${METRIC_DAY_OF_COMPLETION}`,
        0,
        'border-left-warning',
        GROUP_ROW_2,
        [PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW]
    ),
    new Metric(
        METRIC_TASKS_COMPLETED,
        `dashboard.${METRIC_TASKS_COMPLETED}`,
        0,
        'border-left-success',
        GROUP_ROW_3,
        [PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW],
        () => import('./components/TasksBadge'),
        (start, end, value) => Promise.resolve({
            data: value.perAgreement.map(row => {
                row.involved_dpt01 = row.dpt01 ? 1 : 0;
                row.involved_dpt02 = row.dpt02 ? 1 : 0;
                row.involved_dpt03 = row.dpt03 ? 1 : 0;
                row.involved_dpt04 = row.dpt04 ? 1 : 0;
                row.involved_dpt05 = row.dpt05 ? 1 : 0;
                return row;
            })
        })
    )
];
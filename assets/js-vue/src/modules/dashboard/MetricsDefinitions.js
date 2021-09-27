import Metric, {
    METRIC_FACTORS_MONTHLY_LIMIT,
    METRIC_ORDERS_FINISHED,
    METRIC_ORDERS_PENDING,
    METRIC_WORKING_DAYS_COUNT,
    METRIC_DAY_OF_COMPLETION,
    GROUP_ROW_1,
    GROUP_ROW_2,
} from "./metrics/DashboardMetric";
import ProductionMetric from "./metrics/ProductionMetric";
import {getProductionFinishedDetails, getProductionPendingDetails} from "./repository";

export default [
    new Metric(
        METRIC_WORKING_DAYS_COUNT,
        `dashboard.${METRIC_WORKING_DAYS_COUNT}`,
        0,
        'border-left-primary',
        GROUP_ROW_1
    ),
    new Metric(
        METRIC_FACTORS_MONTHLY_LIMIT,
        `dashboard.${METRIC_FACTORS_MONTHLY_LIMIT}`,
        0,
        'border-left-primary',
        GROUP_ROW_1
    ),
    new ProductionMetric(
        METRIC_ORDERS_PENDING,
        `dashboard.${METRIC_ORDERS_PENDING}`,
        0,
        'border-left-primary',
        GROUP_ROW_2,
        (start, end) => getProductionPendingDetails(null, end)
    ),
    new ProductionMetric(
        METRIC_ORDERS_FINISHED,
        `dashboard.${METRIC_ORDERS_FINISHED}`,
        0,
        'border-left-success',
        GROUP_ROW_2,
        (start, end) => getProductionFinishedDetails(start, end)
    ),
    new Metric(
        METRIC_DAY_OF_COMPLETION,
        `dashboard.${METRIC_DAY_OF_COMPLETION}`,
        0,
        'border-left-warning',
        GROUP_ROW_2
    ),
];
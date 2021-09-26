import Metric, {
    METRIC_FACTORS_MONTHLY_LIMIT, METRIC_ORDERS_FINISHED,
    METRIC_ORDERS_PENDING,
    METRIC_WORKING_DAYS_COUNT
} from "./DashboardMetric";
import ProductionMetric from "./ProductionMetric";
import {getProductionCompletedDetails, getProductionPendingDetails} from "./repository";

export default [
    new Metric(
        METRIC_WORKING_DAYS_COUNT,
        `dashboard.${METRIC_WORKING_DAYS_COUNT}`,
        0,
        'border-left-primary'
    ),
    new Metric(
        METRIC_FACTORS_MONTHLY_LIMIT,
        `dashboard.${METRIC_FACTORS_MONTHLY_LIMIT}`,
        0,
        'border-left-primary'
    ),
    new ProductionMetric(
        METRIC_ORDERS_PENDING,
        `dashboard.${METRIC_ORDERS_PENDING}`,
        0,
        'border-left-primary',
        (start, end) => getProductionPendingDetails(null, end)
    ),
    new ProductionMetric(
        METRIC_ORDERS_FINISHED,
        `dashboard.${METRIC_ORDERS_FINISHED}`,
        0,
        'border-left-success',
        (start, end) => getProductionCompletedDetails(start, end)
    )
];
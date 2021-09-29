export const METRIC_WORKING_DAYS_COUNT = 'workingDays';
export const METRIC_FACTORS_MONTHLY_LIMIT = 'factorLimit';
export const METRIC_ORDERS_FINISHED = 'orders_finished';
export const METRIC_ORDERS_PENDING = 'orders_pending';
export const METRIC_DAY_OF_COMPLETION = 'firstFreeDay';

export const GROUP_ROW_1 = 'row1';
export const GROUP_ROW_2 = 'row2';

export default class Metric
{
    id;
    title;
    value;
    color;
    className;
    groupId;
    detailsPromise;
    busy;

    constructor(
        id,
        title,
        value,
        className,
        groupId,
        grants = [],
        component = null,
        detailsPromise = null)
    {
        this.id = id;
        this.title = title;
        this.value = value;
        this.className = className;
        this.grants = grants
        this.groupId = groupId;
        this.component = component || (() => import('../components/BaseBadge'));
        this.detailsPromise = detailsPromise;
        this.busy = true;
    }

    getValue() {
        return this.value;
    }

    fetchDetails(start, end) {
        return this.detailsPromise !== null
            ? this.detailsPromise(start, end)
            : Promise.resolve();
    }
}
export default {
    title: 'Metric trends',
    breadcrumb: {
        home: 'Dashboard',
        reports: 'Reports',
        current: 'Metric trends',
    },
    filters: {
        all_departments: 'All departments',
        cumulative: 'Cumulative from the 1st',
        daily: 'Daily change',
        factors: 'Factor',
        count: 'Count',
        compare: 'Compare with previous month',
    },
    computed_at: 'Last recalculation',
    never_computed: 'not calculated yet',
    previous_month: 'previous month',
    week: 'Week {number}',
    backfill_note: 'Backfilled - calculated on later data',
    no_data: 'No records for the selected month. The first values appear after the cron run or a manual recalculation.',
    no_data_chart: 'Nothing to show. The daily change needs records from two consecutive days.',
    capture_done: 'Today\'s metric values recalculated.',
    action: {
        capture: 'Recalculate now',
    },
    metric: {
        orders_finished: 'Orders completed',
        orders_pending: 'Orders in progress',
    },
    chart: {
        orders: {
            title: 'Orders',
            description: 'Completed counted from the 1st of the month, in progress - state on the day.',
        },
        bonus: {
            title: 'Completed production tasks',
            description: 'Sum of factors of tasks completed since the 1st of the month, per department.',
        },
        on_time: {
            title: 'Department timeliness',
            description: 'Share of the factor completed on time (with the tolerance used for settlements) in everything completed since the 1st of the month.',
        },
        capacity: {
            title: 'Production department load',
            description: 'Factor planned for the whole selected month, as known on the day.',
        },
        weekly: {
            title: 'Weekly load',
            description: 'Capacity usage of upcoming weeks (order factor / week limit), as known on the day.',
        },
        attention: {
            title: 'Needs attention',
            description: 'Number of items on the attention lists - state on the day. Order lists count orders, production lists count department tasks.',
        },
    },
    error: {
        load: 'Could not load the metric history.',
        capture: 'Could not recalculate the metrics.',
    },
}

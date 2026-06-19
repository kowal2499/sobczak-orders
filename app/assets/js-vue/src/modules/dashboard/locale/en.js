export default {
    'title': 'Dashboard',
    'month_placeholder': 'Please select month',
    'year_placeholder': 'Please select year',
    'orders_pending': 'Orders in production',
    'orders_finished': 'Orders finished',
    'factors_pending': 'Factors summary for all orders in production',
    'factors_completed': 'Factors summary for all finished orders',
    'workingDays': 'Number of working days',
    'factorLimit': 'Monthly factors limit',
    'totalFactors': 'Factors summary for all orders',
    'firstFreeDay': 'Estimated date for the completion of all orders',
    'tasksCompleted': 'Completed production tasks',
    'capacityMetric': 'Production departments capacity',
    'weeklyCapacityMetric': 'Weekly capacity utilization',
    'showForecast': 'Show pending orders forecast',
    'forecastLabel': 'Forecast',
    'ghostOrderBanner': 'Order in forecast — production has not yet been started',

    'layout': {
        'edit': 'Edit layout',
        'done': 'Done editing',
        'reset': 'Restore default layout',
        'hide': 'Hide widget',
        'show': 'Show widget',
    },

    'productionMetric': {
        'baseFactor': 'Base factor',
        'bonus': 'Bonus',
        'penalty': 'Penalty',
        'percentageModifier': 'Percentage modifier',
        'unsupportedValue': 'Unsupported value',
    },

    'descriptions': {
        'capacity': {
            'p1': '<strong>Planning report.</strong> Operates exclusively on production task data — the order status and its planned completion date have no effect on the results.',
            'p2': 'Shows the planned workload for each production department in the selected period. Each task is assigned to a month based on the <strong>planned completion date for the given department</strong> — set when the task is assigned to production.',
            'p3': 'The report includes tasks with a planned completion date within the selected range that have already been assigned to production — both in progress and completed. Bonuses and penalties are not included.',
            'p4': 'Note: tasks spanning more than one month in a given department are visible only in the month of their planned completion, not in the month they started.',
            'p5': 'The report does not show orders for which production has not yet been assigned, unless forecast mode is enabled.',
        },
        'tasksCompleted': {
            'p1': 'The report operates exclusively on production task data — the order status and its planned completion date have no effect on the results.',
            'p2': '<strong>Execution report.</strong> Shows the sum of production factors for tasks <strong>actually completed</strong> in the selected period, broken down by department. Each task is assigned to a month based on the <strong>actual completion date</strong> — the moment it was marked as completed.',
            'p3': 'Delayed tasks (planned for an earlier month but completed later) appear in the month of actual completion. Early completions work the same way — if a task was planned for April but completed in March, it appears in the March report. Bonuses and penalties assigned to tasks are included. Pending or in-progress tasks are not included.',
            'p4': 'Best used for <strong>monthly reconciliation</strong> of work actually performed.',
        },
        'weeklyCapacity': {
            'p1': 'Shows a weekly breakdown of the company\'s <strong>production capacity</strong> against the <strong>workload from accepted orders</strong>. For each week, the progress bar shows how much of the available capacity is occupied by orders with a delivery date falling in that week.',
            'p2': 'Weekly capacity is calculated based on configured daily norms, excluding days off and holidays. The workload is the sum of factors for orders whose <strong>confirmed delivery date</strong> falls in that week — these are not production task dates, but dates agreed with the customer.',
            'p3': 'The report only includes orders for which production has been initiated. Orders without a production assignment do not affect the result, unless forecast mode is enabled.',
        },
    }
}
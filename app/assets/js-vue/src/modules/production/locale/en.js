export default {
    startContext: {
        deliveryDate: 'Delivery date',
        receiveDate: 'Received',
        noData: 'No information',
        showDescription: 'Show description',
        hideDescription: 'Hide description',
    },
    planCalendar: {
        allDepartments: 'All departments',
        showHeatmap: 'Show department load',
        capacityTitle: 'Company load this week (by delivery dates): {burned} / {capacity}',
        showExistingTasks: 'Show existing tasks',
        noAccess: 'no access',
        loadTitle: 'Tasks in department: {count}',
        loadError: 'Could not load department workload',
    },
    productionScheduler: {
        strategyFast: {
            name: 'Fast Strategy 6-2-2-0-0-0-thursday',
            description: 'End dates: Gluing: 6 days before end, CNC and Intorex 2 days before end, other departments: end date = completion date for the whole. Start date is the end date in the previous department.'
        },
        strategyCascade: {
            name: 'Strategy 14-9-9-7-2-0-thursday',
            description: 'End dates: Gluing: 14 days, CNC and Intorex: 9 days, Grinding: 7 days, Varnishing: 2 days, Packaging: 0 days before Thursday. Start date is the end date in the previous department.'
        }
    }
}
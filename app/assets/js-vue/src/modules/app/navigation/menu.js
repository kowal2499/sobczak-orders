// AgreementLine::STATUS_* on the server
const STATUS_WAITING = 5
const STATUS_MANUFACTURING = 10
const STATUS_WAREHOUSE = 15
const STATUS_ARCHIVED = 20
const STATUS_DELETED = 25

function orderStatus(label, status) {
    return {
        label,
        statusId: status,
        to: { name: 'orders', params: status ? { status: String(status) } : {} },
        isActive: route => route.name === 'orders' && Number(route.params.status || 0) === status,
    }
}

function link(label, to, activeOn = [to.name]) {
    return { label, to, isActive: route => activeOn.includes(route.name) }
}

/**
 * The visibility rules mirror the former Twig sidebar, which used is_granted() - hence
 * hasRole()/hasGrant() and never can(), which lets ROLE_ADMIN through every grant.
 */
export function buildMenu(user) {
    const items = []

    items.push({ ...link('menu.dashboard', { name: 'dashboard' }), icon: 'fa fa-tachometer' })

    if (user.hasRole('ROLE_PRODUCTS')) {
        items.push({
            ...link('menu.products', { name: 'products' }, ['products', 'product-new', 'product-edit']),
            icon: 'fa fa-star',
        })
    }

    if (user.hasRole('ROLE_CUSTOMERS')) {
        items.push({
            ...link('menu.customers', { name: 'customers' }, ['customers', 'customer-new', 'customer-edit']),
            icon: 'fa fa-users',
        })
    }

    const orders = [
        orderStatus('menu.ordersAll', 0),
        orderStatus('menu.ordersWaiting', STATUS_WAITING),
        orderStatus('menu.ordersManufacturing', STATUS_MANUFACTURING),
        orderStatus('menu.ordersWarehouse', STATUS_WAREHOUSE),
        orderStatus('menu.ordersArchived', STATUS_ARCHIVED),
    ]
    if (user.hasRole('ROLE_ADMIN')) {
        orders.push(orderStatus('menu.ordersDeleted', STATUS_DELETED))
    }
    items.push({
        key: 'orders',
        label: 'menu.orders',
        icon: 'fa fa-address-book-o',
        children: orders,
        activeOn: ['orders', 'order-new', 'order-edit', 'agreement-line'],
    })

    if (user.hasRole('ROLE_PRODUCTION_VIEW')) {
        items.push({ ...link('menu.production', { name: 'production' }), icon: 'fa fa-cogs' })
    }

    const calendars = [
        user.hasGrant('reports.calendar_tasks') && link('menu.calendarTasks', { name: 'calendar-tasks' }),
        user.hasGrant('reports.calendar_orders') && link('menu.calendarOrders', { name: 'calendar-orders' }),
        user.hasGrant('reports.calendar_general') && link('menu.calendarGeneral', { name: 'calendar-general' }),
    ].filter(Boolean)
    if (calendars.length) {
        items.push({
            key: 'calendars',
            label: 'menu.calendars',
            icon: 'fa fa-calendar',
            children: calendars,
            activeOn: ['calendar-tasks', 'calendar-orders', 'calendar-general'],
        })
    }

    const reports = [
        user.hasRole('ROLE_ADMIN') && link('menu.reportDifferences', { name: 'report-differences' }),
        user.hasGrant('bonus-settlement.view') && link('menu.bonusSettlement', { name: 'bonus-settlement' }),
    ].filter(Boolean)
    if (reports.length) {
        items.push({
            key: 'reports',
            label: 'menu.reports',
            icon: 'fa fa-line-chart',
            children: reports,
            activeOn: ['report-differences', 'bonus-settlement'],
        })
    }

    if (user.hasRole('ROLE_ADMIN')) {
        items.push({ divider: true })
        items.push({
            key: 'configuration',
            label: 'menu.configuration',
            icon: 'fa fa-wrench',
            children: [
                link('menu.users', { name: 'users' }, ['users', 'user-edit', 'user-new']),
                link('menu.roles', { name: 'roles' }),
                link('menu.tags', { name: 'tags' }),
                link('menu.workConfiguration', { name: 'work-configuration' }),
            ],
            activeOn: ['users', 'user-edit', 'user-new', 'roles', 'tags', 'work-configuration'],
        })
    }

    return items
}

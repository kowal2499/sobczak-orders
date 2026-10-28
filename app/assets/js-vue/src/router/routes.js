import { agreementStatusesMap } from '@/helpers'

const statusNames = () => Object.fromEntries(
    Object.entries(agreementStatusesMap).map(([code, s]) => [code, s.name])
)

const id = param => route => ({ [param]: Number(route.params.id) })

/**
 * meta.access mirrors the #[IsGranted] the former Symfony view action had; it only decides what
 * the router shows - every data endpoint keeps its own server-side check.
 */
export default [
    {
        path: '/',
        name: 'dashboard',
        component: () => import('@/modules/dashboard/Dashboard.vue'),
        meta: { title: 'app.title.dashboard' },
    },
    {
        path: '/orders/add',
        name: 'order-new',
        component: () => import('@/modules/app/pages/OrderFormPage.vue'),
        meta: { title: 'app.title.orderNew' },
    },
    {
        path: '/orders/edit/:id(\\d+)',
        name: 'order-edit',
        component: () => import('@/modules/app/pages/OrderFormPage.vue'),
        props: id('agreementId'),
        meta: { title: 'app.title.orderEdit' },
    },
    {
        path: '/orders/:status(\\d+)?',
        name: 'orders',
        component: () => import('@/components/orders/list/OrdersList.vue'),
        props: route => ({ status: Number(route.params.status || 0), taskStatuses: statusNames() }),
        meta: { title: 'app.title.orders' },
    },
    {
        path: '/agreement/line/:id(\\d+)',
        name: 'agreement-line',
        component: () => import('@/components/orders/single/SingleOrder.vue'),
        props: route => ({ lineId: Number(route.params.id), taskStatuses: statusNames() }),
        meta: { title: 'app.title.agreementLine' },
    },
    {
        path: '/production',
        name: 'production',
        component: () => import('@/modules/agreementLineRM/views/AgreementLineListRM.vue'),
        props: () => ({ taskStatuses: statusNames() }),
        meta: { title: 'app.title.production', access: user => user.hasRole('ROLE_PRODUCTION_VIEW') },
    },
    {
        path: '/reports/schedule/view/production',
        name: 'calendar-tasks',
        component: () => import('@/modules/schedule/views/CalendarTasks.vue'),
        meta: { title: 'app.title.calendarTasks', access: user => user.hasGrant('reports.calendar_tasks') },
    },
    {
        path: '/reports/schedule/view/orders',
        name: 'calendar-orders',
        component: () => import('@/modules/schedule/views/CalendarOrders.vue'),
        meta: { title: 'app.title.calendarOrders', access: user => user.hasGrant('reports.calendar_orders') },
    },
    {
        path: '/reports/schedule/view/production/basic',
        name: 'calendar-general',
        component: () => import('@/modules/schedule/views/CalendarGeneral.vue'),
        meta: { title: 'app.title.calendarGeneral', access: user => user.hasGrant('reports.calendar_general') },
    },
    {
        path: '/reports/differences',
        name: 'report-differences',
        component: () => import('@/modules/reports/DifferencesReport/index.vue'),
        meta: { title: 'app.title.reportDifferences', access: user => user.hasRole('ROLE_ADMIN') },
    },
    {
        path: '/bonus-settlement',
        name: 'bonus-settlement',
        component: () => import('@/modules/bonusSettlement/index.vue'),
        meta: { title: 'app.title.bonusSettlement', access: user => user.hasGrant('bonus-settlement.view') },
    },
    {
        path: '/metric-history',
        name: 'metric-history',
        component: () => import('@/modules/metricHistory/index.vue'),
        meta: { title: 'app.title.metricHistory', access: user => user.hasGrant('metric-snapshot.view') },
    },
    {
        path: '/users',
        name: 'users',
        component: () => import('@/modules/app/pages/UsersPage.vue'),
        meta: { title: 'app.title.users', access: user => user.hasRole('ROLE_ADMIN') },
    },
    {
        path: '/users/add',
        name: 'user-new',
        component: () => import('@/modules/app/pages/UserPage.vue'),
        meta: { title: 'app.title.userNew', access: user => user.hasRole('ROLE_ADMIN') },
    },
    {
        path: '/users/edit/:id(\\d+)',
        name: 'user-edit',
        component: () => import('@/modules/app/pages/UserPage.vue'),
        props: id('userId'),
        meta: { title: 'app.title.userEdit', access: user => user.hasRole('ROLE_ADMIN') },
    },
    {
        path: '/authorization/role/view',
        name: 'roles',
        component: () => import('@/modules/app/pages/RolesPage.vue'),
        meta: { title: 'app.title.roles' },
    },
    {
        path: '/tag-definition',
        name: 'tags',
        component: () => import('@/modules/app/pages/TagsPage.vue'),
        meta: { title: 'app.title.tags' },
    },
    {
        path: '/work-configuration',
        name: 'work-configuration',
        component: () => import('@/modules/app/pages/WorkConfigurationPage.vue'),
        meta: { title: 'app.title.workConfiguration' },
    },
    {
        path: '/dev',
        name: 'dev',
        component: () => import('@/components/dev/DevContainer.vue'),
        meta: { title: 'Dev', access: user => user.hasGrant('authorization.admin') },
    },
    {
        path: '*',
        name: 'not-found',
        component: () => import('@/modules/app/pages/NotFoundPage.vue'),
        meta: { title: 'app.title.notFound' },
    },
]

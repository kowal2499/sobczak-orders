import { buildMenu } from '@/modules/app/navigation/menu'
import { User } from '@/services/privilages'

function user(roles, grants = []) {
    const u = new User({ roles })
    u.setGrants(grants)
    return u
}

const labels = items => items.flatMap(i => [i.label, ...(i.children || []).map(c => c.label)]).filter(Boolean)

// Reachable roles as /me returns them for each base role (security.yaml role_hierarchy)
const ADMIN = ['ROLE_ADMIN', 'ROLE_SETTINGS', 'ROLE_PRODUCTION', 'ROLE_CUSTOMERS', 'ROLE_PRODUCTS', 'ROLE_CUSTOMERS_LIMITED', 'ROLE_PRODUCTION_VIEW']
const SALES = ['ROLE_USER', 'ROLE_CUSTOMERS', 'ROLE_PRODUCTS', 'ROLE_PRODUCTION_VIEW']
const CUSTOMER = ['ROLE_CUSTOMER', 'ROLE_CUSTOMERS_LIMITED', 'ROLE_PRODUCTION_VIEW']

describe('buildMenu', () => {
    it('shows the bonus settlement entry to a user holding its grant', () => {
        const menu = labels(buildMenu(user(SALES, ['bonus-settlement.view'])))

        expect(menu).toContain('menu.reports')
        expect(menu).toContain('menu.bonusSettlement')
        expect(menu).not.toContain('menu.reportDifferences')
    })

    it('keeps the reports group for an admin, but without the grant-only bonus entry', () => {
        const menu = labels(buildMenu(user(ADMIN)))

        expect(menu).toContain('menu.reportDifferences')
        expect(menu).not.toContain('menu.bonusSettlement')
    })

    it('hides admin sections and the trash from a sales user', () => {
        const menu = labels(buildMenu(user(SALES)))

        expect(menu).toEqual(expect.arrayContaining(['menu.dashboard', 'menu.products', 'menu.customers', 'menu.production']))
        expect(menu).not.toContain('menu.reports')
        expect(menu).not.toContain('menu.configuration')
        expect(menu).not.toContain('menu.calendars')
        expect(menu).not.toContain('menu.ordersDeleted')
    })

    it('hides products and customers from a customer account', () => {
        const menu = labels(buildMenu(user(CUSTOMER)))

        expect(menu).not.toContain('menu.products')
        expect(menu).not.toContain('menu.customers')
        expect(menu).toContain('menu.production')
    })

    it('shows only the calendars the user is granted', () => {
        const items = buildMenu(user(SALES, ['reports.calendar_orders']))
        const calendars = items.find(i => i.key === 'calendars')

        expect(calendars.children.map(c => c.label)).toEqual(['menu.calendarOrders'])
    })

    it('treats the authorization.admin grant as every grant', () => {
        const calendars = buildMenu(user(SALES, ['authorization.admin'])).find(i => i.key === 'calendars')

        expect(calendars.children).toHaveLength(3)
    })

    it('keeps the products entry active on its form routes', () => {
        const products = buildMenu(user(SALES)).find(i => i.label === 'menu.products')

        expect(products.to).toEqual({ name: 'products' })
        expect(products.isActive({ name: 'product-edit', params: { id: '3' } })).toBe(true)
        expect(products.isActive({ name: 'customers', params: {} })).toBe(false)
    })

    it('marks the order status entry active by the route param', () => {
        const orders = buildMenu(user(ADMIN)).find(i => i.key === 'orders')
        const waiting = orders.children.find(c => c.label === 'menu.ordersWaiting')
        const all = orders.children.find(c => c.label === 'menu.ordersAll')

        expect(waiting.isActive({ name: 'orders', params: { status: '5' } })).toBe(true)
        expect(all.isActive({ name: 'orders', params: { status: '5' } })).toBe(false)
        expect(all.isActive({ name: 'orders', params: {} })).toBe(true)
    })
})

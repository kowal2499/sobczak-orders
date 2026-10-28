import Vue from 'vue'
import VueRouter from 'vue-router'
import routes from './routes'
import i18n from '../../i18n'

Vue.use(VueRouter)

export function isAllowed(route, user) {
    return route.matched.every(record => !record.meta.access || record.meta.access(user))
}

export function createRouter(user) {
    const router = new VueRouter({
        mode: 'history',
        routes,
        scrollBehavior: (to, from, saved) => saved || (to.path !== from.path ? { x: 0, y: 0 } : undefined),
    })

    // A deploy removes the previous build, so a tab opened before it cannot fetch the lazy chunk of the
    // next view. A full load of the target fetches the current build instead of a broken page.
    router.onError((error, to) => {
        if (/Loading (CSS )?chunk .* failed/i.test(error.message) && to) {
            window.location.assign(to.fullPath)
        }
    })

    router.afterEach(to => {
        const key = isAllowed(to, user) ? to.meta.title : 'app.title.forbidden'
        document.title = key ? `${i18n.t(key)} - Sobczak` : 'Sobczak'
    })

    return router
}

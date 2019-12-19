import Vue from 'vue'
import Router from 'vue-router'
import { interopDefault } from './utils'
import scrollBehavior from './router.scrollBehavior.js'

import _3296e3ae from '../pages/myy/index.vue'
import _014931dc from '../pages/myy/calendar.vue'
import _3ccc54ee from '../pages/myy/childcare.vue'
import _671fd2fc from '../pages/myy/childcare-visits.vue'
import _5001b270 from '../pages/myy/dashboard.vue'
import _dbde6eac from '../pages/myy/membership.vue'
import _ff4e6702 from '../pages/myy/orders-receipts.vue'
import _10c6871b from '../pages/myy/profile-settings.vue'
import _ed661b48 from '../pages/myy/visits.vue'
import _b90a3420 from '../pages/index.vue'

Vue.use(Router)

export const routerOptions = {
  mode: 'history',
  base: decodeURI('/'),
  linkActiveClass: 'nuxt-link-active',
  linkExactActiveClass: 'nuxt-link-exact-active',
  scrollBehavior,

  routes: [{
      path: "/myy",
      component: () => _3296e3ae.default || _3296e3ae,
      name: "myy"
    }, {
      path: "/myy/calendar",
      component: () => _014931dc.default || _014931dc,
      name: "myy-calendar"
    }, {
      path: "/myy/childcare",
      component: () => _3ccc54ee.default || _3ccc54ee,
      name: "myy-childcare"
    }, {
      path: "/myy/childcare-visits",
      component: () => _671fd2fc.default || _671fd2fc,
      name: "myy-childcare-visits"
    }, {
      path: "/myy/dashboard",
      component: () => _5001b270.default || _5001b270,
      name: "myy-dashboard"
    }, {
      path: "/myy/membership",
      component: () => _dbde6eac.default || _dbde6eac,
      name: "myy-membership"
    }, {
      path: "/myy/orders-receipts",
      component: () => _ff4e6702.default || _ff4e6702,
      name: "myy-orders-receipts"
    }, {
      path: "/myy/profile-settings",
      component: () => _10c6871b.default || _10c6871b,
      name: "myy-profile-settings"
    }, {
      path: "/myy/visits",
      component: () => _ed661b48.default || _ed661b48,
      name: "myy-visits"
    }, {
      path: "/",
      component: () => _b90a3420.default || _b90a3420,
      name: "index"
    }],

  fallback: false
}

export function createRouter() {
  return new Router(routerOptions)
}

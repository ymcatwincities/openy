import Vue from 'vue'
import Router from 'vue-router'
import { interopDefault } from './utils'
import scrollBehavior from './router.scrollBehavior.js'

import _3d231418 from '../pages/myy/index.vue'
import _6f516668 from '../pages/myy/calendar.vue'
import _7063c0f3 from '../pages/myy/childcare.vue'
import _4268c44c from '../pages/myy/dashboard.vue'
import _46f76b00 from '../pages/myy/membership.vue'
import _541b0069 from '../pages/myy/orders-receipts.vue'
import _956a5f1e from '../pages/myy/profile-settings.vue'
import _07b3db5a from '../pages/index.vue'

Vue.use(Router)

export const routerOptions = {
  mode: 'history',
  base: decodeURI('/'),
  linkActiveClass: 'nuxt-link-active',
  linkExactActiveClass: 'nuxt-link-exact-active',
  scrollBehavior,

  routes: [{
      path: "/myy",
      component: () => _3d231418.default || _3d231418,
      name: "myy"
    }, {
      path: "/myy/calendar",
      component: () => _6f516668.default || _6f516668,
      name: "myy-calendar"
    }, {
      path: "/myy/childcare",
      component: () => _7063c0f3.default || _7063c0f3,
      name: "myy-childcare"
    }, {
      path: "/myy/dashboard",
      component: () => _4268c44c.default || _4268c44c,
      name: "myy-dashboard"
    }, {
      path: "/myy/membership",
      component: () => _46f76b00.default || _46f76b00,
      name: "myy-membership"
    }, {
      path: "/myy/orders-receipts",
      component: () => _541b0069.default || _541b0069,
      name: "myy-orders-receipts"
    }, {
      path: "/myy/profile-settings",
      component: () => _956a5f1e.default || _956a5f1e,
      name: "myy-profile-settings"
    }, {
      path: "/",
      component: () => _07b3db5a.default || _07b3db5a,
      name: "index"
    }],

  fallback: false
}

export function createRouter() {
  return new Router(routerOptions)
}

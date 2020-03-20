import Vue from 'vue'
import App from './App.vue'
import Vuex from 'vuex';
import VueRouter from 'vue-router'

Vue.use(VueRouter)
Vue.use(Vuex);

export default {
  init(storeData, routes) {
    const router = new VueRouter({
      mode: 'history',
      base: process.env.BASE_URL,
      routes
    })
    const store = new Vuex.Store(storeData)
    
    new Vue({
      store,
      router,
      render: h => h(App)
    }).$mount('#app')
  }
}
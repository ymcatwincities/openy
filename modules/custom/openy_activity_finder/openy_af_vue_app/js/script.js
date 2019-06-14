import Vue from 'vue'
import VueRouter from 'vue-router'
import ActivityFinder from '../components/ActivityFinder.vue'
import afHomepage from '../components/Homepage.vue'
import afAge from '../components/AgeStep.vue'
import afDay from '../components/DayStep.vue'
import afActivity from '../components/ActivityStep.vue'
import afLocation from '../components/LocationStep.vue'

Vue.use(VueRouter);

const router = new VueRouter({
  mode: 'hash',
  base: drupalSettings.activityFinder.alias + '/',
  routes: [
    { path: '/', component: afHomepage, name: 'home'},
    { path: '/age', component: afAge, name: 'af-age'},
    { path: '/day', component: afDay, name: 'af-day'},
    { path: '/activity', component: afActivity, name: 'af-activity'},
    { path: '/location', component: afLocation, name: 'af-location'}
  ]
});

new Vue({
  delimiters: ['[{', '}]'],
  router: router,
  components: {
    ActivityFinder
  },
}).$mount('#activity-finder-app');

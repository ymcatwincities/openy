import CampFinder from '../components/CampFinder.vue'
import afAge from '../../openy_af_vue_app/components/AgeStep.vue'
import afWeek from '../components/WeekStep.vue'
import afActivity from '../../openy_af_vue_app/components/ActivityStep.vue'
import afLocation from '../../openy_af_vue_app/components/LocationStep.vue'

Vue.use(VueRouter);

const router = new VueRouter({
  mode: 'hash',
  base: drupalSettings.activityFinder.alias + '/',
  routes: [
    { path: '/age', component: afAge, name: 'af-age'},
    { path: '/week', component: afWeek, name: 'af-week'},
    { path: '/activity', component: afActivity, name: 'af-activity'},
    { path: '/location', component: afLocation, name: 'af-location'}
  ]
});

new Vue({
  delimiters: ['[{', '}]'],
  router: router,
  components: {
    CampFinder
  },
}).$mount('#camp-finder-app');

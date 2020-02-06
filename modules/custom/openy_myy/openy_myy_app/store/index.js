import Vue from 'vue'
import Vuex from 'vuex'
import VueCookie from 'vue-cookie';

Vue.use(VueCookie);
Vue.use(Vuex);

const store = () => new Vuex.Store({

  state: {
    isLoggedIn: Vue.cookie.get('Drupal.visitor.personify_authorized')
  },
  mutations: {
    logout (state) {
      state.isLoggedIn = false;
    }
  }
})

export default store

<template>
  <div>
    <myy-header/>
    <section class="myy-main">
      <div class="container">
        <div class="row">
          <div class="col-sm-3">
            <sidebar-menu/>
          </div>
          <div class="col-sm-9">
            <visits/>
            <household/>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
  import MyyHeader from '~/components/MyyHeader.vue'
  import SidebarMenu from '~/components/SidebarMenu.vue'
  import Visits from '~/components/Visits.vue'
  import Household from '~/components/Household.vue'
  import VueCookie from 'vue-cookie';
  import Vue from 'vue'
  Vue.use(VueCookie);

  export default {
    components: {
      MyyHeader,
      SidebarMenu,
      Visits,
      Household
    },
    created: function() {
      // @todo Only for dev purposes, remove before launch.
      if (typeof Drupal === 'undefined') {
        this.$cookie.set('Drupal.visitor.personify_authorized', '203dcb9c-b2d3-4163-b59a-eb83437d41a4');
        this.$cookie.set('Drupal.visitor.personify_id', '2052596923');
        this.$cookie.set('Drupal.visitor.personify_time', '1567520392');
        this.$cookie.set('SESS1d4a7c030a658eb491c69621ef4614db', '7oVaupY_R7YG9UZbvLX7xbakurIjZmWsPKXfBHaQX-E');
      }
    }
  }
</script>

<style>
</style>


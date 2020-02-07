<template>
  <div>
    <myy-header/>
    <mobile-menu/>
    <section class="myy-main">
      <div class="container">
        <div class="row">
          <div class="col-myy-md-3 d-myy-none d-myy-lg-block">
            <sidebar-menu/>
          </div>
          <div class="col-myy-12 col-myy-lg-9">
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
  import MobileMenu from '~/components/MobileMenu.vue'
  import Visits from '~/components/Visits.vue'
  import Household from '~/components/Household.vue'
  import VueCookie from 'vue-cookie';
  import Vue from 'vue'
  Vue.use(VueCookie);

  export default {
    components: {
      MyyHeader,
      MobileMenu,
      SidebarMenu,
      Visits,
      Household
    }
  }
</script>

<style>
</style>


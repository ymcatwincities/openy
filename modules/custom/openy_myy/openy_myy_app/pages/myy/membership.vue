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
            <membership/>
            <guest-passes/>
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
  import Membership from '~/components/Membership.vue'
  import Household from '~/components/Household.vue'
  import GuestPasses from '~/components/GuestPasses.vue'

  export default {
    components: {
      MyyHeader,
      MobileMenu,
      SidebarMenu,
      Membership,
      Household,
      GuestPasses
    }
  }
</script>

<style>
</style>


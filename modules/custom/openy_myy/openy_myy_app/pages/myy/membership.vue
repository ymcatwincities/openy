<template>
  <div>
    <myy-header/>
    <section class="myy-main">
      <div class="container">
        <div class="row">
          <div class="col-myy-sm-3 d-none d-md-block visible-md hidden-xs">
            <sidebar-menu/>
          </div>
          <div class="col-myy-sm-12 col-myy-md-9 col-myy-12">
            <pending-payments/>
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
  import PendingPayments from '~/components/PendingPayments.vue'
  import Household from '~/components/Household.vue'
  import GuestPasses from '~/components/GuestPasses.vue'

  export default {
    components: {
      MyyHeader,
      SidebarMenu,
      PendingPayments,
      Household,
      GuestPasses
    }
  }
</script>

<style>
</style>


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


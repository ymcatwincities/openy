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

  export default {
    components: {
      MyyHeader,
      SidebarMenu,
      Visits,
      Household
    }
  }
</script>

<style>
</style>


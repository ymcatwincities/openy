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
            <childcare/>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
  import MyyHeader from '~/components/MyyHeader.vue'
  import SidebarMenu from '~/components/SidebarMenu.vue'
  import Childcare from '~/components/Childcare.vue'

  export default {
    components: {
      MyyHeader,
      SidebarMenu,
      Childcare,
    }
  }
</script>

<style>
</style>


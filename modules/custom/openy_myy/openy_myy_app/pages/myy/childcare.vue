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
  import MobileMenu from '~/components/MobileMenu.vue'

  export default {
    components: {
      MyyHeader,
      MobileMenu,
      SidebarMenu,
      Childcare,
    }
  }
</script>

<style>
</style>


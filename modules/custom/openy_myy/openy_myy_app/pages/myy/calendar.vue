<template>
  <section class="container">
    <div class="row">
      <div class="col-myy-md-3 d-myy-none d-myy-lg-block">
        <sidebar-menu/>
      </div>
      <div class="col-myy-12 col-myy-lg-9">
        calendar
      </div>
    </div>
  </section>
</template>

<script>
  import MyyHeader from '~/components/MyyHeader.vue'
  import SidebarMenu from '~/components/SidebarMenu.vue'
  import MobileMenu from '~/components/MobileMenu.vue'

  export default {
    components: {
      MyyHeader,
      MobileMenu,
      SidebarMenu,
    }
  }
</script>

<style>
</style>


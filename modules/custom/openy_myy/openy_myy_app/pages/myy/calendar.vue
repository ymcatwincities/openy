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
  import SidebarMenu from '~/components/SidebarMenu.vue'

  export default {
    components: {
      SidebarMenu
    }
  }
</script>

<style>
</style>


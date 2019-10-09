<template>
  <section class="container">
    <div class="row">
      <div class="col-myy-sm-3">
        <sidebar-menu/>
      </div>
      <div class="col-myy-sm-9">
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


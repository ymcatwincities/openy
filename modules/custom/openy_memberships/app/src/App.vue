<template>
  <div id="app" :class="$router.currentRoute.name">
    <router-view />
  </div>
</template>

<script>

export default {
  name: 'App',
  data: () => ({
    //
  }),
};
</script>

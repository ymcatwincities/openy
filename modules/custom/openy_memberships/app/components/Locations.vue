<template>
  <div class="locations">
    <location :key="key" v-for="(location, key) in locations" :name="location.name" :address="location.address" />
  </div>
</template>
<script>
import Location from './Location';

export default {
  props: ['locations'],
  components: {
    Location
  },
}
</script>
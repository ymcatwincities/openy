<template>
  <div class="locations">
    
      <location v-model="value" :value="value" :val="location.value" :key="key" v-for="(location, key) in locations" :name="location.name" :address="location.address" class="location-wrapper" />
  </div>
</template>
<script>
import Location from './Location';

export default {
  props: ['locations'],
  components: {
    Location
  },
  data() {
    return {
      value: null
    }
  }
}
</script>

<style lang="scss" scoped>
.locations {
  display: flex;
  justify-content: flex-start;
}
.location-wrapper {
  min-width: 33.3%;
  padding: 10px;
}
</style>
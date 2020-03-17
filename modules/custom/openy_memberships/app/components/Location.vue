<template>
  <div class="location">
    <div class="name" v-text="name" />
    <div class="address" v-text="address" />
  </div>
</template>
<script>
export default {
  props: ['name', 'address', 'key']
}
</script>

<style lang="scss">
  .location {
    position: relative;
    &::before {
      content: '';
      display: block;
      position: absolute;
      width: 30px;
      height: 100%;
      left: 0;
      top: 0;
      background-image: url('~static/place-24px.svg?data');
      background-position: center;
      background-repeat: no-repeat;
    }
    padding: 10px 10px 10px 30px;
    margin: 10px 0;
    border: 1px solid #000;
    background-color: #f5f5f5;
    .name {
      font-size: 16px;
    }
    .address {
      font-size: 14px;
    }
  }
</style>
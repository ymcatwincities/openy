<template>
  <div>
    <div :class="'location ' + (val == value ? 'active' : '')" @click="updateValue">
      <div class="radio-wrap">
        <div :class="'radio ' + (val == value ? 'active' : '')"></div>
      </div>
      <div class="content">
        <div class="name" v-text="name" />
        <div class="address" v-text="address" />
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: ['name', 'address', 'val', 'value'],
  methods: {
    updateValue() {
      this.$emit('input', this.val)
    }
  }
}
</script>

<style lang="scss">
  .location {
    position: relative;
    cursor: pointer;
    padding: 10px 10px 10px 10px;
    margin: 10px 0;
    display: flex;
    background: #FFFFFF 0% 0% no-repeat padding-box;
    border: 2px solid #0060AF;
    border-radius: 5px;
    .radio {
      border: 1px solid #636466;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      padding: 2px;
      &.active::after {
        border-radius: 50%;
        content: '';
        display: block;
        width: 12px;
        height: 12px;
        background-color: #636466;
      }
    }
    &.active {
      .radio {
        border: 1px solid #FFFFFF;
        &.active::after {
          background-color: #FFFFFF;
        }
      }
      background: #0060AF;
      .content {
        .name {
          color: #FFFFFF;
        }
        .address {
          color: #FFFFFF;
        }
      }
    }
    .radio-wrap {
      min-width: 30px;
    }
    .content {
      min-width: 100%;
      .name {
        font-size: 14px;
        text-align: left;
        font: Bold 14px/21px Verdana;
        letter-spacing: 0;
        color: #0060AF;
      }
      .address {
        font: Regular 12px/21px Verdana;
        color: #231F20;
      }
    }
  }
</style>
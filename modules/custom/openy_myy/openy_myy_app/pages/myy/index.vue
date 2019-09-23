<template>
  <div>
    <myy-header/>
    <section class="myy-main">
      <div class="container">
        <div class="row">
          <div class="col-sm-3">
            <sidebar-menu/>
          </div>
          <div class="col-sm-9">
            content
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import MyyHeader from '~/components/MyyHeader.vue'
import SidebarMenu from '~/components/SidebarMenu.vue'
import Vue from 'vue'
import VueCookie from 'vue-cookie';

Vue.use(VueCookie);

//import 'bootstrap/dist/css/bootstrap.css'
//import 'bootstrap-vue/dist/bootstrap-vue.css'

export default {
/*  middleware ({ store, redirect }) {
    //if (!store.state.authenticated) {
      return redirect('/myy/dashboard')
    //}
  },*/
  components: {
    MyyHeader,
    SidebarMenu
  },
  created: function() {
    // @todo Only for dev purposes, remove before launch.
    if (typeof Drupal === 'undefined') {
      this.$cookie.set('Drupal.visitor.personify_authorized', '203dcb9c-b2d3-4163-b59a-eb83437d41a4');
      this.$cookie.set('Drupal.visitor.personify_id', '2052596923');
      this.$cookie.set('Drupal.visitor.personify_time', '1567520392');
      this.$cookie.set('SESS1d4a7c030a658eb491c69621ef4614db', '7oVaupY_R7YG9UZbvLX7xbakurIjZmWsPKXfBHaQX-E');
    }
  },
}
</script>

<style lang="scss">
  .myy-sub-header {
    background-color: #F2F2F2;
    height: 76px;
    line-height: 76px;
    .back-link {
      display: inline-block;
      background-color: #fff;
      height: 50px;
      line-height: 50px;
      width: 50px;
      border-radius: 25px;
      text-align: center;
      .fa {
        color: #000;
        font-size: 20px;
      }
    }
    h2 {
      margin: 0;
      line-height: 76px;
      font-size: 24px;
      font-family: "Cachet Medium",  sans-serif;
    }
  }
  .myy-main {
    margin-top: 40px;
  }
  * {
    font-family: Verdana, sans-serif;
    font-size: 14px;
  }
  a {
    color: #0060AF;
  }
  .blue {
    color: #0060AF;
  }
  .red {
    color: #CC0000;
  }
  .rounded_letter {
    border-radius: 40px;
    color: #fff;
    display: inline-block;
    font-family: "Cachet Medium", sans-serif;
    font-size: 48px;
    height: 80px;
    line-height: 80px;
    text-align: center;
    width: 80px;
    &.green {
      background-color: #006B6B;
    }
    &.red {
      background-color: #ED1C24;
    }
    &.blue {
      background-color: #00AEEF;
    }
    &.purple {
      background-color: #C6168D;
    }
    &.black {
      background-color: #231F20;
    }
    &.color-0 {
      background-color: #006b6b;
    }
    &.color-1 {
      background-color: #0060af;
    }
    &.color-2 {
      background-color: #5c2e91;
    }
    &.color-3 {
      background-color: #a92b31;
    }
    &.color-4 {
      background-color: #dd5828;
    }
    &.color-5 {
      background-color: #01a490;
    }
    &.color-6 {
      background-color: #0089d0;
    }
    &.color-7 {
      background-color: #92278f;
    }
    &.color-8 {
      background-color: #ed1c24;
    }
    &.color-9 {
      background-color: #f47920;
    }
    &.color-10 {
      background-color: #20bdbe;
    }
    &.color-11 {
      background-color: #00aeef;
    }
    &.color-12 {
      background-color: #c6168d;
    }
    &.color-13 {
      background-color: #f15922;
    }
    &.color-14 {
      background-color: #fcaf17;
    }
    &.small {
      border-radius: 20px;
      font-size: 24px;
      height: 40px;
      line-height: 40px;
      width: 40px;
    }
  }
  .square_number {
    background-color: #F2F2F2;
    border-radius: 5px;
    color: #231F20;
    display: inline-block;
    font-size: 36px;
    height: 80px;
    line-height: 80px;
    text-align: center;
    width: 80px;
    &.small {
      height: 60px;
      line-height: 60px;
      width: 60px;
    }
  }
  .no-padding-left {
    padding-left: 0;
  }
  .no-padding-right {
    padding-right: 0;
  }
  #nuxt-loading {  visibility: hidden;  opacity: 0;  left: 0;  right: 0;  top: 0;  bottom: 0;  display: flex;  justify-content: center;  align-items: center;  flex-direction: column;  animation: nuxtLoadingIn 10s ease;  -webkit-animation: nuxtLoadingIn 10s ease;  animation-fill-mode: forwards;  overflow: hidden;}@keyframes nuxtLoadingIn {  0% {visibility: hidden;opacity: 0;  }  20% {visibility: visible;opacity: 0;  }  100% {visibility: visible;opacity: 1;  }}@-webkit-keyframes nuxtLoadingIn {  0% {visibility: hidden;opacity: 0;  }  20% {visibility: visible;opacity: 0;  }  100% {visibility: visible;opacity: 1;  }}#nuxt-loading>div,#nuxt-loading>div:after {  border-radius: 50%;  width: 5rem;  height: 5rem;}#nuxt-loading>div {  font-size: 10px;  position: relative;  text-indent: -9999em;  border: .5rem solid #F5F5F5;  border-left: .5rem solid #3B8070;  -webkit-transform: translateZ(0);  -ms-transform: translateZ(0);  transform: translateZ(0);  -webkit-animation: nuxtLoading 1.1s infinite linear;  animation: nuxtLoading 1.1s infinite linear;}#nuxt-loading.error>div {  border-left: .5rem solid #ff4500;  animation-duration: 5s;}@-webkit-keyframes nuxtLoading {  0% {-webkit-transform: rotate(0deg);transform: rotate(0deg);  }  100% {-webkit-transform: rotate(360deg);transform: rotate(360deg);  }}@keyframes nuxtLoading {  0% {-webkit-transform: rotate(0deg);transform: rotate(0deg);  }  100% {-webkit-transform: rotate(360deg);transform: rotate(360deg);  }}

  .myy-filters {
    h3 {
      font-size: 18px;
      color: #636466;
      font-family: Verdana, sans-serif;
      margin-bottom: 20px;
    }
    &__wrapper {
      border-left: 1px solid #636466;
      border-right: 1px solid #636466;
      border-top: 1px solid #636466;
    }
    .form-item-date {
      position: relative;
      height: 50px;
      border-bottom: 1px solid #636466;
      padding: 0 10px;
      line-height: 50px;
      label {
        color: #636466;
        display: inline-block;
        font-size: 12px;
        font-weight: bold;
        line-height: 18px;
        text-transform: uppercase;
        margin: 0;
      }
      input {
        border: none;
        background: none;
        display: inline-block;
        font-size: 12px;
        height: 50px;
        line-height: 50px;
        color: #231F20;
        padding: 0;
        margin: 0;
        font-style: italic;
        left: 115px;
        position: absolute;
        top: 1px;
        right: 0;
        outline: none !important;
        z-index: 2;
        &:focus {
          box-shadow: none;
          outline: none !important;
        }
        &:-webkit-autofill,
        &:-webkit-autofill:hover,
        &:-webkit-autofill:focus,
        &:-webkit-autofill:active  {
          transition: background-color 5000s ease-in-out 0s;
        }
      }
      .fa {
        position: absolute;
        top: 50%;
        margin-top: -7px;
        right: 15px;
        z-index: 1;
      }
    }
  }

</style>


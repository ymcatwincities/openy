<template>
  <div>
    <myy-header/>
    <section class="myy-main">
      <div class="container">
        <div class="row">
          <div class="col-myy-md-3 d-myy-none d-myy-lg-block">
            <sidebar-menu/>
          </div>
          <div class="col-myy-12 col-myy-lg-9">
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

export default {
  middleware ({ store, redirect }) {
    return redirect('/myy/dashboard')
  },
  components: {
    MyyHeader,
    SidebarMenu
  }
}
</script>

<style lang="scss">
  .myy-sub-header {
    background-color: #F2F2F2;
    height: 50px;
    line-height: 50px;
    margin-left: -15px;
    @media (min-width: 992px) {
      height: 76px;
      line-height: 76px;
      margin-left: 0px;
    }
    .back-link {
      background-color: #fff;
      display: inline-block;
      height: 50px;
      line-height: 50px;
      text-align: center;
      width: 50px;
      @media (min-width: 992px) {
        background-color: #fff;
        border-radius: 25px;
        display: inline-block;
        height: 50px;
        line-height: 50px;
        text-align: center;
        width: 50px;
      }
      .fa {
        color: #000;
        font-size: 20px;
      }
    }
    .count {
      font-size: 12px;
      margin-left: 10px;
      color: #231F20;
      font-family: Verdana, sans-serif;
      @media (min-width: 992px) {
        display: none;
      }
    }
    a.purchases {
      color: #0060AF;
      font-size: 12px;
      @media (min-width: 992px) {
        font-size: 14px;
      }
    }
    h2 {
      margin: 0;
      line-height: 50px;
      font-size: 24px;
      font-family: "Cachet Medium",  sans-serif;
      @media (min-width: 992px) {
        line-height: 76px;
      }
    }
  }
  .myy-main {
    margin-top: 20px;
    margin-bottom: 20px;
    font-family: Verdana, sans-serif;
    font-size: 12px;
    color: #231F20;
    @media (min-width: 992px) {
      font-size: 14px;
      margin-top: 40px;
      margin-bottom: 40px;
    }
    button {
      margin: 0;
      padding: 0;
      border: none;
      background: none;
    }
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
    font-size: 24px;
    height: 60px;
    line-height: 60px;
    text-align: center;
    width: 60px;
    @media (min-width: 992px) {
      height: 80px;
      line-height: 80px;
      font-size: 32px;
      width: 80px;
    }
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
      font-size: 18px;
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
    height: 60px;
    line-height: 60px;
    text-align: center;
    width: 60px;
    @media (min-width: 992px) {
      height: 80px;
      line-height: 80px;
      width: 80px;
    }
    &.small {
      height: 60px;
      line-height: 60px;
      width: 60px;
    }
  }
  div.no-padding-left {
    padding-left: 0;
  }
  div.no-padding-right {
    padding-right: 0;
  }
  #nuxt-loading {  visibility: hidden;  opacity: 0;  left: 0;  right: 0;  top: 0;  bottom: 0;  display: flex;  justify-content: center;  align-items: center;  flex-direction: col-myyumn;  animation: nuxtLoadingIn 10s ease;  -webkit-animation: nuxtLoadingIn 10s ease;  animation-fill-mode: forwards;  overflow: hidden;}@keyframes nuxtLoadingIn {  0% {visibility: hidden;opacity: 0;  }  20% {visibility: visible;opacity: 0;  }  100% {visibility: visible;opacity: 1;  }}@-webkit-keyframes nuxtLoadingIn {  0% {visibility: hidden;opacity: 0;  }  20% {visibility: visible;opacity: 0;  }  100% {visibility: visible;opacity: 1;  }}#nuxt-loading>div,#nuxt-loading>div:after {  border-radius: 50%;  width: 5rem;  height: 5rem;}#nuxt-loading>div {  font-size: 10px;  position: relative;  text-indent: -9999em;  border: .5rem solid #F5F5F5;  border-left: .5rem solid #3B8070;  -webkit-transform: translateZ(0);  -ms-transform: translateZ(0);  transform: translateZ(0);  -webkit-animation: nuxtLoading 1.1s infinite linear;  animation: nuxtLoading 1.1s infinite linear;}#nuxt-loading.error>div {  border-left: .5rem solid #ff4500;  animation-duration: 5s;}@-webkit-keyframes nuxtLoading {  0% {-webkit-transform: rotate(0deg);transform: rotate(0deg);  }  100% {-webkit-transform: rotate(360deg);transform: rotate(360deg);  }}@keyframes nuxtLoading {  0% {-webkit-transform: rotate(0deg);transform: rotate(0deg);  }  100% {-webkit-transform: rotate(360deg);transform: rotate(360deg);  }}

</style>


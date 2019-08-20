<template>
  <div>
    <myy-header/>
    <section class="container">
      <div class="row">
        <div class="col-sm-3">
          <sidebar-menu/>
        </div>
        <div class="col-sm-9">
          content
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import MyyHeader from '~/components/MyyHeader.vue'
import SidebarMenu from '~/components/SidebarMenu.vue'
import Vue from 'vue'
import BootstrapVue from 'bootstrap-vue'

Vue.use(BootstrapVue)

import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'

export default {
  components: {
    MyyHeader,
    SidebarMenu
  }
}
</script>

<style>
  #nuxt-loading {  visibility: hidden;  opacity: 0;  position: absolute;  left: 0;  right: 0;  top: 0;  bottom: 0;  display: flex;  justify-content: center;  align-items: center;  flex-direction: column;  animation: nuxtLoadingIn 10s ease;  -webkit-animation: nuxtLoadingIn 10s ease;  animation-fill-mode: forwards;  overflow: hidden;}@keyframes nuxtLoadingIn {  0% {visibility: hidden;opacity: 0;  }  20% {visibility: visible;opacity: 0;  }  100% {visibility: visible;opacity: 1;  }}@-webkit-keyframes nuxtLoadingIn {  0% {visibility: hidden;opacity: 0;  }  20% {visibility: visible;opacity: 0;  }  100% {visibility: visible;opacity: 1;  }}#nuxt-loading>div,#nuxt-loading>div:after {  border-radius: 50%;  width: 5rem;  height: 5rem;}#nuxt-loading>div {  font-size: 10px;  position: relative;  text-indent: -9999em;  border: .5rem solid #F5F5F5;  border-left: .5rem solid #3B8070;  -webkit-transform: translateZ(0);  -ms-transform: translateZ(0);  transform: translateZ(0);  -webkit-animation: nuxtLoading 1.1s infinite linear;  animation: nuxtLoading 1.1s infinite linear;}#nuxt-loading.error>div {  border-left: .5rem solid #ff4500;  animation-duration: 5s;}@-webkit-keyframes nuxtLoading {  0% {-webkit-transform: rotate(0deg);transform: rotate(0deg);  }  100% {-webkit-transform: rotate(360deg);transform: rotate(360deg);  }}@keyframes nuxtLoading {  0% {-webkit-transform: rotate(0deg);transform: rotate(0deg);  }  100% {-webkit-transform: rotate(360deg);transform: rotate(360deg);  }}
</style>


<template>
  <div>
    <myy-header/>
    <mobile-menu/>
    <div class="myy-sub-header">
      <div class="container">
        <div class="row">
          <div class="col-myy-6 col-myy-lg-3">
            <nuxt-link to="/myy/dashboard" class="back-link"><i class="fa fa-arrow-left"></i></nuxt-link>
            <strong class="count"><span>0</span> results</strong>
          </div>
          <div class="col-myy-sm-6 text-center d-myy-none d-myy-lg-block">
            <h2>Orders & receipts</h2>
          </div>
          <div class="col-myy-6 col-myy-lg-3 text-right">
            <a href="#" class="purchases"><strong>Purchases</strong></a>
          </div>
        </div>
      </div>
    </div>
    <section class="myy-main">
      <div class="container">
        <div class="row">
          <div class="col-myy-lg-3">
            <filters/>
          </div>
          <div class="col-myy-lg-9">
            <orders-receipts-results
              parameters="parameters"
            />
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
  import MyyHeader from '~/components/MyyHeader.vue'
  import MobileMenu from '~/components/MobileMenu.vue'
  import SidebarMenu from '~/components/SidebarMenu.vue'
  import Filters from '~/components/Filters.vue'
  import OrdersReceiptsResults from '~/components/OrdersReceiptsResults.vue'

  export default {
    components: {
      MyyHeader,
      MobileMenu,
      SidebarMenu,
      OrdersReceiptsResults,
      Filters,
    },
    data () {
      return {
        parameters: [],
        loading: true,
        data: {},
        baseUrl: '/',
        type: 'A'
      }
    },
    methods: {
      triggerRequest: function () {
        this.$emit('runAjaxRequest');
      }
    }

  }
</script>

<style>
</style>


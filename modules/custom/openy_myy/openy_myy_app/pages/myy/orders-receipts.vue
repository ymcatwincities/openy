<template>
  <div>
    <myy-header/>
    <div class="myy-sub-header">
      <div class="container">
        <div class="row">
          <div class="col-myy-sm-3">
            <a @click="$router.go(-1)" class="back-link"><i class="fa fa-arrow-left"></i></a>
          </div>
          <div class="col-myy-sm-6 text-center">
            <h2>Orders & receipts</h2>
          </div>
          <div class="col-myy-sm-3 text-right">
            <a href="#" class="purchases"><strong>Purchases</strong></a>
          </div>
        </div>
      </div>
    </div>
    <section class="myy-main">
      <div class="container">
        <div class="row">
          <div class="col-myy-md-3 d-myy-none d-myy-lg-block">
            <filters/>
          </div>
          <div class="col-myy-12 col-myy-lg-9">
            <orders-receipts-results/>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
  import MyyHeader from '~/components/MyyHeader.vue'
  import Filters from '~/components/Filters.vue'
  import OrdersReceiptsResults from '~/components/OrdersReceiptsResults.vue'

  export default {
    components: {
      MyyHeader,
      OrdersReceiptsResults,
      Filters,
    }
  }
</script>

<style>
</style>


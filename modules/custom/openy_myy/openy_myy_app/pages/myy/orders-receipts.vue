<template>
  <div>
    <myy-header/>
    <section class="myy-main">
      <div class="container">
        <div class="row">
          <div class="col-myy-sm-3 d-none d-md-block visible-md hidden-xs">
            <filters/>
          </div>
          <div class="col-myy-sm-12 col-myy-md-9 col-myy-12">
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


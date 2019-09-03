<template>
  <div>
    <myy-header/>
    <section class="myy-main">
      <div class="container">
        <div class="row">
          <div class="col-sm-3">
            <filters/>
          </div>
          <div class="col-sm-9">
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


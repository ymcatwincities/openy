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
            <h2>Visits</h2>
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
          <div class="col-myy-12 col-myy-lg-9">
            <childcare-visits/>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
  import MyyHeader from '~/components/MyyHeader.vue'
  import SidebarMenu from '~/components/SidebarMenu.vue'
  import MobileMenu from '~/components/MobileMenu.vue'
  import ChildcareVisits from '~/components/ChildcareVisits.vue'
  import Filters from '~/components/Filters.vue'

  export default {
    components: {
      MyyHeader,
      MobileMenu,
      SidebarMenu,
      ChildcareVisits,
      Filters,
    },
    data () {
      return {
        start_date: '',
        end_date: '',
      }
    },
    created: function() {
    },
    mounted: function () {
      var component = this;
      component.$watch('start_date', function(newValue, oldValue) {
        this.$router.push({ query: {
          start_date: newValue,
          end_date: component.end_date
        }});
      });
      component.$watch('end_date', function(newValue, oldValue) {
        this.$router.push({ query: {
          start_date: component.start_date,
          end_date: newValue,
        }});
      });
      jQuery('#edit-start-date').datepicker({
        format: 'mm/dd/yy'
      }).trigger('change').on('change', function () {
        component.start_date = this.value;
      });
      jQuery('#edit-end-date').datepicker({
        format: 'mm/dd/yy'
      }).trigger('change').on('change', function () {
        component.end_date = this.value;
      });
    }
  }
</script>

<style>
</style>


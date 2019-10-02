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
            <h2>Childcare visits</h2>
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
          <div class="col-myy-sm-3">
            <div class="myy-filters">
              <h3>FILTERS</h3>
              <div class="myy-filters__wrapper">
                <div class="form-item form-item-date">
                  <label for="edit-start-date">Start Date</label>
                  <input class="myy-datepicker form-text form-control text" data-drupal-selector="edit-start-date" data-disable-refocus="true" type="text" id="edit-start-date" v-model="start_date" name="start_date" value="" size="60" maxlength="128" />
                  <i class="fa fa-calendar"></i>
                </div>
                <div class="form-item form-item-date">
                  <label for="edit-end-date">End Date</label>
                  <input class="myy-datepicker form-text form-control text" data-drupal-selector="edit-end-date" data-disable-refocus="true" type="text" id="edit-end-date" v-model="end_date" name="end_date" value="" size="60" maxlength="128" />
                  <i class="fa fa-calendar"></i>
                </div>
              </div>
            </div>
          </div>
          <div class="col-myy-sm-9">
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
  import ChildcareVisits from '~/components/ChildcareVisits.vue'

  export default {
    components: {
      MyyHeader,
      SidebarMenu,
      ChildcareVisits,
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


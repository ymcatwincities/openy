<template>
  <section class="myy-orders-filters">
    <a href="#" data-toggle="collapse" data-target=".myy-filters" role="button" class="myy-orders-filters__toggle collapsed" aria-expanded="true">
      REFINE RESULTS
      <i aria-hidden="true" class="fa fa-plus-circle plus ml-auto"></i>
      <i aria-hidden="true" class="fa fa-minus-circle minus ml-auto"></i>
    </a>
    <div class="myy-filters">
      <h3>FILTERS</h3>
      <div class="myy-filters__wrapper">
        <div class="form-item form-item-date">
          <label for="edit-start-date">Start Date</label>
          <input class="myy-datepicker start form-text form-control text" data-drupal-selector="edit-start-date" data-disable-refocus="true" type="text" id="edit-start-date" v-model="start" v-on:change="changed" name="start_date" value="" size="60" maxlength="128" />
          <i class="fa fa-calendar"></i>
        </div>
        <div class="form-item form-item-date">
          <label for="edit-end-date">End Date</label>
          <input class="myy-datepicker end form-text form-control text" data-drupal-selector="edit-end-date" data-disable-refocus="true" type="text" id="edit-end-date" v-model="end" name="end_date" v-on:change="changed" value="" size="60" maxlength="128" />
          <i class="fa fa-calendar"></i>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
  export default {
    data () {
      return {
      }
    },
    methods: {
      changed: function () {
        let component = this;
        component.$router.push({query: { start: component.start, end: component.end }});
        component.$parent.start = component.start;
      }
    },
    created: function() {
      let component = this;
      component.start = typeof component.$route.query.start == 'undefined' ? '2018-01-01' : component.$route.query.start;
      component.end = typeof component.$route.query.end  == 'undefined' ? '2021-01-01' : component.$route.query.end;
      component.$router.push({query: { start: component.start, end: component.end }});
    },
    mounted: function() {
      let component = this;

      jQuery('.myy-datepicker.start').datepicker({
        format: "YYYY-MM-DD",
        multidate: false,
        keyboardNavigation: false,
        forceParse: false,
        autoclose: true,
        todayHighlight: true
      }).on('change', function() {
        if (jQuery(this).val() != '') {
          component.start = moment(jQuery(this).datepicker('getDate')).format('YYYY-MM-DD');
          component.$router.push({query: { start: component.start, end: component.end }});
        }
      });

      jQuery('.myy-datepicker.end').datepicker({
        format: "YYYY-MM-DD",
        multidate: false,
        keyboardNavigation: false,
        forceParse: false,
        autoclose: true,
        todayHighlight: true
      }).on('change', function() {
        if (jQuery(this).val() != '') {
          component.end = moment(jQuery(this).datepicker('getDate')).format('YYYY-MM-DD');
          component.$router.push({query: { start: component.start, end: component.end }});
        }
      });
    }
  }
</script>

<style lang="scss">
  .myy-orders-filters {
    margin-bottom: 20px;
    &__toggle {
      align-items: center;
      background-color: #fff;
      border: 1px solid #636466;
      color: #636466;
      display: flex;
      font-size: 14px;
      font-weight: bold;
      line-height: 21px;
      margin: 0 0 -1px;
      padding: 13px 10px;
      text-transform: uppercase;
      @media (min-width: 992px) {
        display: none;
      }
      &:hover,
      &focus {
        color: #636466;
        text-decoration: none;
      }
      .fa {
        font-size: 15px;
        color: #636466;
      }
      .fa-plus-circle {
        display: none;
      }
      &.collapsed {
        .fa-minus-circle {
          display: none;
        }
        .fa-plus-circle {
          display: inline-block;
        }
      }
    }
    h3 {
      color: #636466;
      font-size: 18px;
      line-height: 18px;
      margin: 0 0 20px;
      display: none;
      @media (min-width: 992px) {
        display: block;
      }
    }
    .myy-filters {
      display: none;
      @media (min-width: 992px) {
        display: block !important;
      }
      &.collapsing,
      &.collapse.in,
      &.collapse.show {
        display: block !important;
      }
    }
  }
</style>


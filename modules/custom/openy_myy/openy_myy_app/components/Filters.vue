<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-orders-filters">
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
        <div class="form-item form-item-household">
          <div class="checkbox-wrapper">
            <div>
              <a href="#" class="d-flex checkbox-toggle-subset hidden">
                <label>Household</label> <i aria-hidden="true" class="fa fa-plus-circle plus ml-auto"></i>
              </a>
              <a href="#" class="d-flex checkbox-toggle-subset" style="">
                <label>Household</label> <i aria-hidden="true" class="fa fa-minus-circle minus ml-auto"></i></a>
            </div>
            <div v-for="(item, index) in data.household" v-bind:key="index" class="item">
                <div class="checkbox-wrapper">
                <div>
                  <input v-if="typeof item.RelatedMasterCustomerId !== 'undefined'" type="checkbox" :id="'checkbox-category-filter-' + item.RelatedMasterCustomerId"  :value="item.RelatedMasterCustomerId" v-on:change="changed">
                  <input v-else type="checkbox" :id="'checkbox-category-filter-' + uid" :value="uid" v-on:change="changed">
                  <label v-if="typeof item.RelatedMasterCustomerId !== 'undefined'" :for="'checkbox-category-filter-' + item.RelatedMasterCustomerId">{{ item.name }}</label>
                  <label v-else :for="'checkbox-category-filter-' + uid">{{ item.name }}</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
  export default {
    data () {
      return {
        start: '',
        end: '',
        household: {},
        loading: true,
        baseUrl: '/',
        data: {
          uid: '',
          household: []
        }
      }
    },
    methods: {
      changed: function () {
        let component = this;
        component.$router.push({query: {
          start: component.start,
          end: component.end,
          household: component.household
        }});
        component.updateQuery();
        component.$parent.triggerRequest();
      },
      updateQuery: function() {
        let component = this;
        component.start = typeof component.$route.query.start == 'undefined' ? '2018-01-01' : component.$route.query.start;
        component.end = typeof component.$route.query.end  == 'undefined' ? '2021-01-01' : component.$route.query.end;
        var household = [];
        for (var i in component.data.household) {
          if (typeof component.data.household[i].RelatedMasterCustomerId != 'undefined') {
            household.push(component.data.household[i].RelatedMasterCustomerId);
          }
          else {
            household.push(component.uid);
          }
        }
        component.household = household.join(',');
        component.$router.push({query: {
          start: component.start,
          end: component.end,
          household: component.household
        }});
      },
      runAjaxRequest: function() {
        let component = this,
            url = component.baseUrl + 'myy-model/data/profile/family-list';

        component.loading = true;
        jQuery.ajax({
          url: url,
          xhrFields: {
            withCredentials: true
          }
        }).done(function(data) {
          component.data = data;
          component.updateQuery();
          component.loading = false;
        });
      }
    },
    created: function() {
    },
    mounted: function() {
      let component = this;

      if (typeof window.drupalSettings === 'undefined') {
        var drupalSettings = {
          path: {
            baseUrl: 'http://openy-demo.docksal/',
          }
        };
        window.drupalSettings = drupalSettings;
      }
      component.baseUrl = window.drupalSettings.path.baseUrl;
      component.uid = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.uid : '';

      component.runAjaxRequest();

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
        margin: 0;
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
          box-shadow: none;
          display: inline-block;
          font-size: 12px;
          height: 50px;
          line-height: 50px;
          color: #231F20;
          padding: 0 0 0 115px;
          margin: 0;
          font-style: italic;
          left: 0;
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
      .form-item-household {
        border-bottom: 1px solid #636466;
        line-height: 50px;
        margin: 0;
        position: relative;
        .checkbox-toggle-subset label {
          color: #636466;
          display: inline-block;
          font-size: 12px;
          font-weight: bold;
          line-height: 18px;
          margin: 0;
          text-transform: uppercase;
          padding: 0 10px;
        }
        .item {
          background-color: #f2f2f2;
          padding: 0 10px;
        }
      }
    }
  }
</style>

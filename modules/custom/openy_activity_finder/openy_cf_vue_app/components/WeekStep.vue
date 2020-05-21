<template>
  <div id="af-week">
    <div class="activity-finder__step">
      <div class="activity-finder__step_wrapper">
        <div class="activity-finder__step_header">
          <div class="activity-finder__step_header--progress">
            <div class="container">
              <div class="activity-finder__step_header--progress-inner">
                <div class="d-inline-flex">
                  <span v-if="this.$parent.loading">
                    <spinner></spinner>
                  </span>
                  <span v-else class="activity-finder__step_header--progress-spacer">
                    <strong>{{ count }} programs</strong><span v-if="this.$parent.previousStepFilters"><strong> for:</strong> {{ this.$parent.previousStepFilters }}</span>
                    <span class="activity-finder__step_header--progress-spacer">|</span>
                    <a :href="this.$parent.previousStepQuery">View All</a>
                  </span>
                </div>
                <div class="d-inline-flex ml-auto text-right start-over-wrapper">
                  <a href="#" @click.prevent="startOver()" class="start_over">Start Over</a>
                </div>
              </div>
            </div>
          </div>

          <div class="activity-finder__step_header--actions">
            <div class="row">
              <div class="col-12 col-xs-12 col-sm-8">
                <span v-if="filtersBreadcrumbs === ''">When are you interested in attending camp?</span>
                <span v-else><strong>Filters: </strong>{{ filtersBreadcrumbs }}</span>
              </div>
              <div v-if="!this.$parent.loading" class="col-xs-12 col-sm-4 text-right ml-auto actions-buttons">
                <button @click.prevent="skip()" v-bind:disabled="!isStepNextDisabled" class="btn btn-primary skip btn-lg">Skip</button>
                <button @click.prevent="next()" v-bind:disabled="isStepNextDisabled" class="btn btn-primary btn-lg next btn-disabled">Next</button>
              </div>
            </div>
          </div>
        </div>

        <!-- It is extremely important to pass $route.query.ages as default value, otherwise initializeFromGet happens -->
        <!-- later from initializing this component and checkedAges will be overidden to empty value. -->
        <main-filter
          v-if="!this.$parent.loading"
          :options="weeksOptions"
          :order="weeksOrder"
          type="multiple"
          :default='$route.query.weeks'
          v-on:updated-values="checkedWeeks = $event"
          col-count=3
        ></main-filter>

        <div class="activity-finder__step_footer">
          <div class="activity-finder__step_header--actions">
            <div v-if="!this.$parent.loading" class="row">
              <div class="col-xs-12 col-sm-12 text-right ml-auto actions-buttons">
                <button @click.prevent="skip()" v-bind:disabled="!isStepNextDisabled" class="btn btn-primary skip btn-lg">Skip</button>
                <button @click.prevent="next()" v-bind:disabled="isStepNextDisabled" class="btn btn-primary btn-lg next btn-disabled">Next</button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>

<script>
  import Spinner from '../../openy_af_vue_app/components/Spinner.vue'
  import MainFilter from '../../openy_af_vue_app/components/Filter.vue'

  export default {
    data () {
      return {
        checkedWeeks: [],
        filtersBreadcrumbs: '',
        isStepNextDisabled: true,
      };
    },
    components: {
      Spinner,
      MainFilter
    },
    computed: {
      weeksOptions: function() {
        var options = {};

        for (var i in this.$parent.weeks) {
          var item = this.$parent.weeks[i];
          options[item.value] = {
            'label': item.label,
            'count': this.weekCounters[item.value]
          };
        }
        return {
          'Week(s)' : options
        };
      },
      weeksOrder: function() {
        var order = {};

        order['Week(s)'] = [];
        for (var i in this.$parent.weeks) {
          var item = this.$parent.weeks[i];
          order['Week(s)'].push(item.value);
        }
        return order;
      },
      count: function() {
        return this.$parent.table.count;
      },
      weekCounters: function () {
        var counters = {};
        if (typeof this.$parent.table.facets.static_weeks_filter == 'undefined') {
          return counters;
        }
        for (var key in this.$parent.table.facets.static_weeks_filter) {
          counters[this.$parent.table.facets.static_weeks_filter[key].value] = this.$parent.table.facets.static_weeks_filter[key].count;
        }
        return counters;
      }
    },
    methods: {
      skip: function () {
        this.$parent.$emit('skip', 'week');
      },
      next: function () {
        this.$parent.$emit('next', 'week');
      },
      startOver: function () {
        this.$parent.$emit('startOver');
      },
      cardSelected: function(data, value) {
        return this.$parent.cardSelected(data, value);
      },
      buildBreadcrumbs: function (value) {
        var breadcrumbs = '';
        if (value) {
          var filters = [];
          for (var i in value) {
            filters.push(this.$parent.getWeekNameById(value[i]))
          }
          breadcrumbs = filters.join(', ');
        }
        return breadcrumbs;
      },
      weeksSelected: function () {
        return this.checkedWeeks.length;
      },
      weekCounter: function(weekId) {
        if (typeof this.weekCounters[weekId] == 'undefined') {
          return 0;
        }
        return this.weekCounters[weekId];
      }
    },
    watch: {
      'checkedWeeks': function(value) {
        let component = this.$parent;
        component.checkedWeeks = value;
        this.isStepNextDisabled = component.checkedWeeks.length === 0;
        this.filtersBreadcrumbs = this.buildBreadcrumbs(value);
      }
    }
  }
</script>

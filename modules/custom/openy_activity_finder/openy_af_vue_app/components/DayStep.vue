<template>
  <div id="af-day">
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
                    <strong>{{ count }} programs</strong><span v-if="previousStepFilters"><strong> for:</strong> {{ previousStepFilters }}</span>
                    <span class="activity-finder__step_header--progress-spacer">|</span>
                    <a :href="viewAllUrl">View All</a>
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
                <span v-if="filtersBreadcrumbs === ''">What days are you looking to fill?</span>
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
          :options="daysOptions"
          type="multiple"
          :default='$route.query.days'
          v-on:updated-values="checkedDays = $event"
        ></main-filter>

        <div class="activity-finder__step_footer">
          <div class="activity-finder__step_header--actions">
            <div class="row">
              <div class="col-12 col-xs-12 col-sm-8">
                <span v-if="filtersBreadcrumbs === ''">What days are you looking to fill?</span>
                <span v-else><strong>Filters: </strong>{{ filtersBreadcrumbs }}</span>
              </div>
              <div v-if="!this.$parent.loading" class="col-xs-12 col-sm-4 text-right ml-auto actions-buttons">
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
  import Spinner from '../components/Spinner.vue'
  import MainFilter from '../components/Filter.vue'

  export default {
    data () {
      return {
        checkedDays: [],
        filtersBreadcrumbs: '',
        previousStepFilters: '',
        isStepNextDisabled: true,
        daysMap: {
          'monday': 1,
          'tuesday': 2,
          'wednesday': 3,
          'thursday' : 4,
          'friday': 5,
          'saturday': 6,
          'sunday': 7
        }
      };
    },
    components: {
      Spinner,
      MainFilter
    },
    mounted: function () {
      this.previousStepFilters = this.$parent.previousStepFilters;
    },
    computed: {
      daysOptions: function() {
        var options = {};

        for (var dayName in this.daysMap) {
          var id = this.daysMap[dayName];
          var counter = this.dayCounters[id];
          options[id] = {
            label: dayName,
            count: counter
          };
        }
        return {
          'Day(s)' : options
        };
      },
      count: function() {
        return this.$parent.table.count;
      },
      viewAllUrl: function() {
        // @todo get all checked filters from previous steps.
        return this.$parent.programSearchUrl;
      },
      dayCounters: function () {
        var counters = {};
        if (typeof this.$parent.table.facets.days_of_week == 'undefined') {
          return counters;
        }
        for (var key in this.$parent.table.facets.days_of_week) {
          var filter = this.$parent.table.facets.days_of_week[key].filter;
          if (typeof this.daysMap[filter] !== 'undefined') {
            filter = this.daysMap[filter];
          }
          counters[filter] = this.$parent.table.facets.days_of_week[key].count;
        }
        return counters;
      }
    },
    methods: {
      skip: function () {
        this.$parent.$emit('skip', 'day');
      },
      next: function () {
        this.$parent.$emit('next', 'day');
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
            filters.push(this.$parent.getDayNameById(value[i]))
          }
          breadcrumbs = filters.join(', ');
        }
        return breadcrumbs;
      },
      daysSelected: function () {
        return this.checkedDays.length;
      },
      dayCounter: function(dayId) {
        if (typeof this.dayCounters[dayId] == 'undefined') {
          return 0;
        }
        return this.dayCounters[dayId];
      }
    },
    watch: {
      'checkedDays': function(value) {
        let component = this.$parent;
        //component.initializeFromGet();
        component.checkedDays = value;
        this.isStepNextDisabled = component.checkedDays.length === 0;
        this.filtersBreadcrumbs = this.buildBreadcrumbs(value);
      }
    }
  }
</script>

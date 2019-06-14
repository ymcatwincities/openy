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
                    <strong>{{ count }} programs</strong>
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

        <div class="activity-finder__step_content" v-if="!this.$parent.loading">
          <div class="activity-finder__collapse_group">
            <a class="activity-finder__collapse_group__link" href="#">
              <h3>Day(s)</h3>
              <span v-if="daysSelected() > 0" class="badge badge-pill badge-dark px-3 py-2">{{ daysSelected() }}</span>
            </a>
              <div class="row">
                <div v-for="(item, index) in this.$parent.days" class="col col-4 col-xs-4 col-sm-6 col-md-3">
                  <div :class="{ 'openy-card__item': true, 'no-results':(dayCounter(item.value) === 0), 'selected': cardSelected(checkedDays, item.value) }">
                    <label :for="'af-day-filter-' + item.value" class="justify-content-center" data-mh="openy-card__item-label--days">
                      <input v-if="dayCounter(item.value) !== 0" v-model="checkedDays" type="checkbox" :value="item.value" :id="'af-day-filter-' + item.value" class="d-none hidden" />
                      <div class="d-flex flex-column">
                        <span>{{ item.label }}</span>
                        <small>{{ dayCounter(item.value) }} results</small>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

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

  export default {
    data () {
      return {
        checkedDays: [],
        filtersBreadcrumbs: '',
        isStepNextDisabled: true,
        daysMap: {
          'monday': 1,
          'tuesday': 2,
          'wednesday': 3,
          'thursday' : 4,
          'friday': 5,
          'saturday': 6,
          'sunday': 7,
        }
      };
    },
    components: {
      Spinner
    },
    computed: {
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

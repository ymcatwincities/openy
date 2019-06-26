<template>
  <div id="af-age">
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
                <span v-if="filtersBreadcrumbs === ''">How old are the people you-re searching for?</span>
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
          :options="agesOptions"
          type="multiple"
          :default='$route.query.ages'
          v-on:updated-values="checkedAges = $event"
        ></main-filter>

        <div class="activity-finder__step_footer">
          <div class="activity-finder__step_header--actions">
            <div class="row">
              <div class="col-12 col-xs-12 col-sm-8">
                <span v-if="filtersBreadcrumbs === ''">How old are the people you-re searching for?</span>
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
        checkedAges: [],
        filtersBreadcrumbs: '',
        isStepNextDisabled: true
      };
    },
    components: {
      Spinner,
      MainFilter
    },
    computed: {
      // Prepare structure to pass to Filter component.
      agesOptions: function() {
        var options = {};
        for (var i in this.$parent.ages) {
          var item = this.$parent.ages[i];
          options[item.value] = {
            'label': item.label,
            'count': this.ageCounters[item.value]
          };
        }

        return {
          'Age(s)': options
        };
      },
      count: function() {
        return this.$parent.table.count;
      },
      viewAllUrl: function() {
        // @todo get all checked filters from previous steps.
        return this.$parent.programSearchUrl;
      },
      ageCounters: function () {
        var counters = {};
        if (typeof this.$parent.table.facets.static_age_filter == 'undefined') {
          return counters;
        }
        for (var key in this.$parent.table.facets.static_age_filter) {
          counters[this.$parent.table.facets.static_age_filter[key].value] = this.$parent.table.facets.static_age_filter[key].count;
        }
        return counters;
      }
    },
    methods: {
      skip: function () {
        this.$parent.$emit('skip', 'age');
      },
      next: function () {
        this.$parent.$emit('next', 'age');
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
            filters.push(this.$parent.getAgeNameById(value[i]))
          }
          breadcrumbs = filters.join(', ');
        }
        return breadcrumbs;
      },
      agesSelected: function () {
        return this.checkedAges.length;
      },
      ageCounter: function(ageId) {
        if (typeof this.ageCounters[ageId] == 'undefined') {
          return 0;
        }
        return this.ageCounters[ageId];
      }
    },
    watch: {
      'checkedAges': function(value) {
        let component = this.$parent;
        //component.initializeFromGet();
        component.checkedAges = value;
        this.isStepNextDisabled = component.checkedAges.length === 0;
        this.filtersBreadcrumbs = this.buildBreadcrumbs(value);
      }
    }
  }
</script>

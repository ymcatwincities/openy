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

        <div class="activity-finder__step_content" v-if="!this.$parent.loading">
          <div class="activity-finder__collapse_group">
            <a class="activity-finder__collapse_group__link" href="#">
              <h3>Age(s)</h3>
              <span v-if="agesSelected() > 0" class="badge badge-pill badge-dark px-3 py-2">{{ agesSelected() }}</span>
            </a>
            <div class="row">
              <div v-for="(item, index) in this.$parent.ages" class="col col-4 col-xs-4 col-sm-6 col-md-3">
                <div :class="{ 'openy-card__item': true, 'no-results':(ageCounter(item.value) === 0), 'selected': cardSelected(checkedAges, item.value) }">
                  <label :for="'af-age-filter-' + item.value" class="justify-content-center" data-mh="openy-card__item-label--ages">
                    <input v-if="ageCounter(item.value) !== 0" v-model="checkedAges" type="checkbox" :value="item.value" :id="'af-age-filter-' + item.value" class="d-none hidden" />
                    <div class="d-flex flex-column">
                      <span>{{ item.label }}</span>
                      <small>{{ ageCounter(item.value) }} results</small>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

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

  export default {
    data () {
      return {
        checkedAges: [],
        filtersBreadcrumbs: '',
        isStepNextDisabled: true,
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
      ageCounters: function () {
        var counters = {};
        if (typeof this.$parent.table.facets.static_age_filter == 'undefined') {
          return counters;
        }
        for (var key in this.$parent.table.facets.static_age_filter) {
          counters[this.$parent.table.facets.static_age_filter[key].filter] = this.$parent.table.facets.static_age_filter[key].count;
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

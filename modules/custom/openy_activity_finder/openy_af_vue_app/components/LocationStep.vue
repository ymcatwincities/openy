<template>
  <div id="af-location">
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
                <span v-if="filtersBreadcrumbs === ''">Do you have any location preferences?</span>
                <span v-else><strong>Filters: </strong>{{ filtersBreadcrumbs }}</span>
              </div>
              <div v-if="!this.$parent.loading" class="col-xs-12 col-sm-4 text-right ml-auto actions-buttons">
                <button @click.prevent="skip()" v-bind:disabled="!isStepNextDisabled" class="btn btn-primary skip btn-lg">Skip</button>
                <button @click.prevent="next()" v-bind:disabled="isStepNextDisabled" class="btn btn-primary btn-lg next btn-disabled">Next</button>
              </div>
            </div>
          </div>
        </div>

        <main-filter
          v-if="!this.$parent.loading"
          :options="locationsOptions"
          type="multiple"
          :default='$route.query.locations'
          v-on:updated-values="checkedLocations= $event"
          col-count=2
        ></main-filter>

        <div class="activity-finder__step_footer">
          <div class="activity-finder__step_header--actions">
            <div v-if="!this.$parent.loading" class="row">
              <div class="col-12 col-xs-12 col-sm-8">
                <span v-if="filtersBreadcrumbs === ''">Do you have any location preferences?</span>
                <span v-else><strong>Filters: </strong>{{ filtersBreadcrumbs }}</span>
              </div>
              <div class="col-xs-12 col-sm-4 text-right ml-auto actions-buttons">
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
        checkedLocations: [],
        filtersBreadcrumbs: '',
        previousStepFilters: '',
        isStepNextDisabled: true,
      };
    },
    components: {
      Spinner,
      MainFilter
    },
    computed: {
      locationsOptions: function() {
        var options = {};

        for (let i in this.$parent.locations) {
          let topLevelLabel = this.$parent.locations[i].label;
          let secondLevelOptions = {};
          for (let j in this.$parent.locations[i].value) {
            let id = this.$parent.locations[i].value[j].value;
            let label = this.$parent.locations[i].value[j].label;
            secondLevelOptions[id] = {
              label: label,
              count: this.locationCounter(id)
            };
          }

          options[topLevelLabel] = secondLevelOptions;
        }
        return options;
      },
      count: function() {
        return this.$parent.table.count;
      },
      locationCounters: function () {
        var counters = {};
        if (typeof this.$parent.table.facets.locations == 'undefined') {
          return counters;
        }
        for (var key in this.$parent.table.facets.locations) {
          counters[this.$parent.table.facets.locations[key].id] = this.$parent.table.facets.locations[key].count;
        }

        return counters;
      }
    },
    methods: {
      skip: function () {
        this.$parent.$emit('skip', 'location');
      },
      next: function () {
        this.$parent.$emit('next', 'location');
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
            filters.push(this.$parent.getLocationNameById(value[i]))
          }
          breadcrumbs = filters.join(', ');
        }

        return breadcrumbs;
      },
      locationSelected: function(e) {
        let count = 0;
        for (var i in this.$parent.locations) {
          if (this.$parent.locations[i].label === e) {
            for (var j in this.$parent.locations[i].value) {
              let value = this.$parent.locations[i].value[j].value.toString();
              if (this.checkedLocations.indexOf(value) != -1) {
                count++;
              }
            }
          }
        }
        return count;
      },
      locationCounter: function(locationId) {
        if (typeof this.locationCounters[locationId] == 'undefined') {
          return 0;
        }
        return this.locationCounters[locationId];
      }
    },
    watch: {
      'checkedLocations': function(value) {
        let component = this.$parent;
        component.checkedLocations = value;
        this.isStepNextDisabled = component.checkedLocations.length === 0;
        this.filtersBreadcrumbs = this.buildBreadcrumbs(value);
      }
    },
    mounted: function () {
      jQuery(function() {
        jQuery('*[data-mh="openy-card__item-label"]').matchHeight();
      });
    }
  }
</script>

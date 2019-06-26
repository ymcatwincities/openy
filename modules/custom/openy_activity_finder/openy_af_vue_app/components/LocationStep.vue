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

        <div class="activity-finder__step_content" v-show="!this.$parent.loading">
          <div v-for="(topLevelLocation, index) in this.$parent.locations" class="activity-finder__collapse_group">
            <a class="activity-finder__collapse_group__link collapsed" data-toggle="collapse" :href="'#collapse-location-group-' + index">
              <h3>{{ topLevelLocation.label }}</h3>
              <span v-if="locationSelected(topLevelLocation.label) > 0" class="badge badge-pill badge-dark px-3 py-2">{{ locationSelected(topLevelLocation.label) }}</span>
              <i class="fa fa-plus-circle"></i>
              <i class="fa fa-minus-circle"></i>
            </a>
            <div :id="'collapse-location-group-' + index" class="row collapse">
              <div v-for="(item, index) in topLevelLocation.value">
                <div class="col-12 col-xs-12 col-sm-6 col-md-4">
                  <div v-bind:class="{'openy-card__item':true, 'no-results':(locationCounter(item.value) === 0), 'selected': cardSelected(checkedLocations, item.value) }">
                    <label :for="'af-filter-location-' + item.value" class="has-subtext" data-mh="openy-card__item-label">
                      <i class="fa fa-map-marker"></i>
                      <input v-if="locationCounter(item.value) !== 0" v-model="checkedLocations" type="checkbox" :value="item.value" :data-label="item.label" :id="'af-filter-location-' + item.value" class="d-none hidden">
                      <div class="d-flex flex-column">
                        <span>{{ item.label }}</span>
                        <small>{{ locationCounter(item.value) }} results</small>
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

      </div>
    </div>

  </div>
</template>

<script>
  import Spinner from '../components/Spinner.vue'

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
        //component.initializeFromGet();
        component.checkedLocations = value;
        this.isStepNextDisabled = component.checkedLocations.length === 0;
        this.filtersBreadcrumbs = this.buildBreadcrumbs(value);
      }
    }
  }
</script>

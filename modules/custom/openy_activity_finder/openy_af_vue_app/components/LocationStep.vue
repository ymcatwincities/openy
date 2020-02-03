<template>
  <div id="af-location">
    <div class="activity-finder__step">
      <div class="activity-finder__step_wrapper">
        <div class="activity-finder__step_header">
          <div class="activity-finder__step_header--progress container">
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
            <div class="container">
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

        <main-filter
          v-if="!this.$parent.loading"
          :options="locationsOptions"
          :order="locationsOrder"
          type="multiple"
          :default='$route.query.locations'
          v-on:updated-values="checkedLocations= $event"
          col-count=2
        ></main-filter>

        <div class="activity-finder__step_footer">
          <div class="activity-finder__step_header--actions">
            <div class="container">
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

    <div class="modal fade schedule-dashboard__modal schedule-dashboard__modal--home-branch-unavailable" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">

          <div class="schedule-dashboard__modal--header">
            <h3>No Results</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fa fa-times" aria-hidden="true"></i></button>
          </div>

          <div class="schedule-dashboard__modal--body row">
            <div class="col-12 col-xs-12">
              <p><strong>Oh no you're headed to an empty results page</strong></p>
              <p>The options you've selected do not match any <br/> activities available at your selected Home Branch.</p>
              <p>Please select one of the options below to proceed:</p>
              <button class="btn btn-primary" type="button" data-dismiss="modal" aria-label="View available locations">View available locations</button>
              <button class="btn btn-primary white-blue" type="button" data-dismiss="modal" aria-label="Adjust my filters" @click.prevent="startOver()">Adjust my filters</button>
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
        homeBranchUnavailable: false,
        executedHomeBranch: false,
      };
    },
    components: {
      Spinner,
      MainFilter
    },
    computed: {
      locationsOptions: function() {
        var options = {};

        for (var i in this.$parent.locations) {
          var topLevelLabel = this.$parent.locations[i].label;
          var secondLevelOptions = {};
          for (var j in this.$parent.locations[i].value) {
            var id = this.$parent.locations[i].value[j].value;
            var label = this.$parent.locations[i].value[j].label;
            secondLevelOptions[id] = {
              label: label,
              count: this.locationCounter(id)
            };
          }

          options[topLevelLabel] = secondLevelOptions;
        }
        return options;
      },
      locationsOrder: function() {
        var order = {};

        for (var i in this.$parent.locations) {
          var topLevelLabel = this.$parent.locations[i].label;
          order[topLevelLabel] = [];
          for (var j in this.$parent.locations[i].value) {
            order[topLevelLabel].push(this.$parent.locations[i].value[j].value);
          }
        }
        return order;
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
          var id = this.$parent.table.facets.locations[key].id;
          counters[id] = this.$parent.table.facets.locations[key].count;
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
      },
      initHomeBranch: function() {
        if (!this.$parent.homeBranchId) {
          return;
        }
        if (this.executedHomeBranch) {
          return;
        }
        if (jQuery('input[value="' + this.$parent.homeBranchId + '"]').length === 0) {
          return;
        }
        this.executedHomeBranch = true;
        var locationId = this.$parent.homeBranchId,
            el = jQuery('input[value="' + locationId + '"]');
        if (el.parent().find('.fa-home').length === 0) {
          el.parent()
            .find('span')
            .prepend('<i class="fa fa-home"></i>');
        }
        // Check if home branch has some results.
        if (typeof this.locationCounters[locationId] != 'undefined' && this.locationCounters[locationId] == 0) {
          // Show home branch unavailable modal.
          this.homeBranchUnavailable = true;
          if (this.homeBranchUnavailable) {
            jQuery('.schedule-dashboard__modal--home-branch-unavailable').modal('show');
          }
        } else {
          // Open filters and pre-select home branch if there are some results.
          // Warning: in case of any future modifications, do not update child's filter value if you want to show
          // alternative results in other locations, not only for home branch.
          if ( this.locationCounters[locationId] > 0) {
            var exist = false;
            for (var i in this.checkedLocations) {
              if (this.checkedLocations[i] == locationId) {
                exist = true;
              }
            }
            if (!exist) {
              this.$set(this.$parent.checkedLocations, this.$parent.checkedLocations.length, locationId);
            }
            el.parents('.openy-card__item')
              .addClass('selected')
              .parents('.openy-card__wrapper')
              .detach()
              .insertBefore(".openy-card__wrapper:eq(0)")
              .parents('.collapse')
              .addClass('in')
              .prev()
              .removeClass('collapsed');
          }
        }
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
    },
    updated: function () {
      this.initHomeBranch();
    }
  }
</script>

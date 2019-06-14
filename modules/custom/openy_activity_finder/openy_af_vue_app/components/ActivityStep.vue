<template>
  <div id="af-activity">
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
                <span v-if="filtersBreadcrumbs === ''">Do the people you're searching for have any prefered activities?</span>
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
          <div v-for="(topLevelCategory, index) in this.$parent.activities" class="activity-finder__collapse_group">
            <a class="activity-finder__collapse_group__link collapsed" data-toggle="collapse" :href="'#collapse-activity-group-' + index">
              <h3>{{ topLevelCategory.label }}</h3>
              <span v-if="categorySelected(topLevelCategory.label) > 0" class="badge badge-pill badge-dark px-3 py-2">{{ categorySelected(topLevelCategory.label) }}</span>
              <i class="fa fa-plus-circle"></i>
              <i class="fa fa-minus-circle"></i>
            </a>
            <div :id="'collapse-activity-group-' + index" class="row collapse">
              <div v-for="(item, index) in topLevelCategory.value">
                <div v-if="categoryIsNotExcluded(item.value)" class="col-12 col-xs-12 col-sm-6 col-md-3">
                  <div v-bind:class="{ 'openy-card__item centered': true, 'no-results':(activityCounter(item.value) === 0), 'selected': cardSelected(checkedCategories, item.value) }">
                    <label :for="'af-filter-category-' + item.value" data-mh="openy-card__item-label">
                      <input v-if="categoriesType === 'single' && activityCounter(item.value) !== 0" v-model="checkedCategories" type="radio" :value="item.value" :data-label="item.label" :id="'af-filter-category-' + item.value" name="activity-radios">
                      <input v-if="categoriesType === 'multiple' && activityCounter(item.value) !== 0" v-model="checkedCategories" type="checkbox" :value="item.value" :data-label="item.label" :id="'af-filter-category-' + item.value" class="hidden d-none" name="activity-radios">
                      <div class="d-flex flex-column">
                        <span>{{ item.label }}</span>
                        <small>{{ activityCounter(item.value) }} results</small>
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
                <span v-if="filtersBreadcrumbs === ''">Do the people you're searching for have any prefered activities?</span>
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
        checkedCategories: [],
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
      categoriesType: function() {
        return this.$parent.categories_type;
      },
      activityCounters: function () {
        var counters = {};
        if (typeof this.$parent.table.facets.field_activity_category == 'undefined') {
          return counters;
        }
        for (var key in this.$parent.table.facets.field_activity_category) {
          counters[this.$parent.table.facets.field_activity_category[key].id] = this.$parent.table.facets.field_activity_category[key].count;
        }

        return counters;
      }
    },
    methods: {
      skip: function () {
        this.$parent.$emit('skip', 'activity');
      },
      next: function () {
        this.$parent.$emit('next', 'activity');
      },
      startOver: function () {
        this.$parent.$emit('startOver');
      },
      cardSelected: function(data, value) {
        return this.$parent.cardSelected(data, value);
      },
      categoryIsNotExcluded: function(value) {
        return this.$parent.categoryIsNotExcluded(value);
      },
      buildBreadcrumbs: function (value) {
        // There are 2 kinds of categories structure: single (daxko), multiple (solr).
        // Group breadcrumbs if multiple, and not in case of single.
        var breadcrumbs = '';

        if (value) {
          var filters = [];
          if (this.$parent.categories_type == 'single') {
            for (var i in value) {
              filters.push(this.$parent.getCategoryNameById(value[i]))
            }
            breadcrumbs = filters.join(', ');
          }
          if (this.$parent.categories_type == 'multiple') {
            var group = {};
            for (var i in value) {
              var category = this.$parent.getCategoryNameById(value[i]),
                  parentCategory = this.$parent.getCategoryParentNameByChildId(value[i]);
              if (typeof group[parentCategory] == 'undefined') {
                group[parentCategory] = [];
              }
              group[parentCategory].push(category);
            }
            for (var key in group) {
              var loc_filters = [];
              for (var j in group[key]) {
                loc_filters.push(group[key][j]);
              }
              filters.push(key + ' (' + loc_filters.join(', ') + ')');
            }
          }
          breadcrumbs = filters.join(', ');
        }

        return breadcrumbs;
      },
      categorySelected: function(e) {
        let count = 0;
        for (var i in this.$parent.activities) {
          if (this.$parent.activities[i].label === e) {
            for (var j in this.$parent.activities[i].value) {
              let value = this.$parent.activities[i].value[j].value;
              if (this.checkedCategories.indexOf(value) != -1) {
                count++;
              }
            }
          }
        }
        return count;
      },
      activityCounter: function(activityId) {
        if (typeof this.activityCounters[activityId] == 'undefined') {
          return 0;
        }
        return this.activityCounters[activityId];
      }
    },
    watch: {
      'checkedCategories': function(value) {
        let component = this.$parent;
        //component.initializeFromGet();
        component.checkedCategories = value;
        this.isStepNextDisabled = component.checkedCategories.length === 0;
        this.filtersBreadcrumbs = this.buildBreadcrumbs(value);
      }
    }
  }
</script>

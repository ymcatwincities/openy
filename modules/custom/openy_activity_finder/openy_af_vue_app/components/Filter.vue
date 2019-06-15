<template>

  <div class="activity-finder__step_content">
    <div v-for="(topLevel, topLevelLabel, TopLevelIndex) in options" class="activity-finder__collapse_group">
      <a class="activity-finder__collapse_group__link collapsed" data-toggle="collapse" :href="'#collapse-activity-group-' + TopLevelIndex">
        <h3>{{ topLevelLabel }}</h3>
        <span v-if="topLevelCounters[topLevelLabel] > 0" class="badge badge-pill badge-dark px-3 py-2">{{ topLevelCounters[topLevelLabel] }}</span>
        <i class="fa fa-plus-circle"></i>
        <i class="fa fa-minus-circle"></i>
      </a>
      <div :id="'collapse-activity-group-' + TopLevelIndex" class="row collapse">
        <div v-for="(secondLevel, secondLevelId, scondLevelIndex) in topLevel">
          <!--<div v-if="categoryIsNotExcluded(item.value)" class="col-12 col-xs-12 col-sm-6 col-md-3">-->
          <div class="col-12 col-xs-12 col-sm-6 col-md-3">
            <!--<div v-bind:class="{ 'openy-card__item centered': true, 'no-results':(secondLevel.count === 0), 'selected': checked.indexOf(secondLevelId) !== -1 }">-->
            <div v-bind:class="{ 'openy-card__item centered': true, 'no-results':(parseInt(secondLevel.count) === 0), 'selected': checked.indexOf(secondLevelId) !== -1 }">
              <label :for="'af-filter-category-' + scondLevelIndex" data-mh="openy-card__item-label">
                <input v-if="type === 'single'" v-model="checked" type="radio" :value="secondLevelId" :data-label="secondLevel.label" :id="'af-filter-category-' + scondLevelIndex" name="filter-radios">
                <input v-if="type === 'multiple'" v-model="checked" type="checkbox" :value="secondLevelId" :data-label="secondLevel.label" :id="'af-filter-category-' + scondLevelIndex" class="hidden d-none" name="filter-checkboxes">
                <div class="d-flex flex-column">
                  <span>{{ secondLevel.label }}</span>
                  <small>{{ secondLevel.count }} results</small>
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  // We expect structure of Options:
  // {
  //  "Swimming": {
  //    314: {
  //      label: "Swimming Lessons",
  //      count: 15
  //    },
  //    394: {
  //      label: "Swimming for Adults",
  //      count: 9
  //    },
  //  }
  export default {
    props: [
      'options', 'type', 'default'
    ],
    data: function() {
      return {
        checked: []
      };
    },
    created: function() {
      this.checked = this.default.split(',');
    },
    computed: {
      topLevelCounters: function() {
        var counters = {};
        for (var topLevelLabel in this.options) {
          var counter = 0;
          for (var secondLevelId in this.options[topLevelLabel]) {
            if (this.checked.indexOf(secondLevelId) !== -1) {
              counter += 1;
            }
          }
          counters[topLevelLabel] = counter;
        }

        return counters;
      }
    },
    watch: {
      checked: function(values) {
        // Some of the values could be empty. Clean them up.
        var cleanValues = [];
        for (var key in values) {
          if (values[key] != '') {
            cleanValues.push(values[key]);
          }
        }
        this.$emit('updated-values', cleanValues);
      }
    }
  }
</script>

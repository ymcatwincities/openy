<template>

  <div class="activity-finder__step_content container">
    <div v-for="(topLevel, topLevelLabel, topLevelIndex) in options" class="activity-finder__collapse_group">
      <a :class="{'activity-finder__collapse_group__link': true, 'collapsed': topLevelCounters[topLevelLabel] == 0}" data-toggle="collapse" :href="'#collapse-activity-group-' + topLevelIndex">
        <h3>{{ topLevelLabel }}</h3>
        <span v-if="topLevelCounters[topLevelLabel] > 0" class="badge badge-pill badge-dark">{{ topLevelCounters[topLevelLabel] }}</span>
        <i class="fa fa-plus-circle"></i>
        <i class="fa fa-minus-circle"></i>
      </a>
      <div :id="'collapse-activity-group-' + topLevelIndex" :class="{'row': true, 'collapse': topLevelCounters[topLevelLabel] == 0}">
        <div v-for="(secondLevel, secondLevelIndex) in order[topLevelLabel]" v-if="filterIsNotExcluded(secondLevel)" v-bind:class="{ 'openy-card__wrapper col-4 col-xs-4 col-sm-3' : colCount == 3, 'openy-card__wrapper col-6 col-xs-6 col-sm-3' : colCount == 2 }">
          <div v-bind:class="{ 'openy-card__item centered': true, 'no-results':(parseInt(topLevel[secondLevel].count) === 0 && topLevel[secondLevel].count !== undefined), 'selected': checked.indexOf(secondLevel + '') > -1 }">
            <label :for="'af-filter-category-' + topLevelIndex + '-'  + secondLevelIndex" data-mh="openy-card__item-label">
              <input v-if="type === 'single'" v-model="checked" type="radio" :value="secondLevel" :data-label="topLevel[secondLevel].label" :id="'af-filter-category-' + topLevelIndex + '-' + secondLevelIndex" class="hidden d-none" name="filter-radios">
              <input v-if="type === 'multiple'" v-model="checked" type="checkbox" :value="secondLevel" :data-label="topLevel[secondLevel].label" :id="'af-filter-category-' + topLevelIndex + '-' + secondLevelIndex" class="hidden d-none" name="filter-checkboxes">
              <div class="d-flex flex-column">
                <span>{{ topLevel[secondLevel].label }}</span>
                <small v-if="topLevel[secondLevel].count !== undefined">{{ topLevel[secondLevel].count }} results</small>
              </div>
            </label>
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
      'options', 'order', 'type', 'default', 'excluded', 'col-count'
    ],
    data: function() {
      return {
        checked: []
      };
    },
    created: function() {
      if (this.default != undefined) {
        this.checked = this.default.split(',');
      }
    },
    computed: {
      topLevelCounters: function() {
        let counters = {};
        for (let topLevelLabel in this.options) {
          var counter = 0;
          for (let secondLevelId in this.options[topLevelLabel]) {
            if (this.checked.indexOf(secondLevelId) !== -1) {
              counter += 1;
            }
          }
          counters[topLevelLabel] = counter;
        }

        return counters;
      }
    },
  methods: {
    filterIsNotExcluded: function (value) {
      for (var i in this.excluded) {
        if (this.excluded[i] == value) {
          return false;
        }
      }
      return true;
    }
  },
    watch: {
      checked: function(values) {
        let returnValues = values;
        if (typeof values == 'string') {
          returnValues = [ values ];
        }
        // Some of the values could be empty. Clean them up.
        var cleanValues = [];
        for (let key in returnValues) {
          if (returnValues[key] != '') {
            cleanValues.push(returnValues[key]);
          }
        }
        this.$emit('updated-values', cleanValues);
      }
    },
    mounted: function () {
      jQuery(function() {
        jQuery('*[data-mh="openy-card__item-label"]').matchHeight();
      });
    }
  }
</script>

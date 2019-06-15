<template>
  <div>
    <router-view class="app-content"></router-view>
  </div>
</template>

<script>
  export default {
    props: ['ages', 'days', 'categories', 'categories_type', 'locations', 'activities'],
    data () {
      return {
        loading: false,
        table: {
          count: 0,
          facets: {
            field_session_min_age: [],
            field_session_max_age: [],
            field_session_time_days: [],
            field_category_program: [],
            field_activity_category: [],
            field_session_location: []
          }
        },
        initialStep: '',
        stepOptions: {
          'age': ['age', 'day', 'activity', 'location'],
          'day': ['day', 'age', 'activity', 'location'],
          'activity': ['activity', 'age', 'day', 'location'],
        },
        keywords: '',
        checkedAges: [],
        checkedDays: [],
        checkedProgramTypes: [],
        checkedCategories: [],
        checkedLocations: [],
        limitCategory: [],
        categoriesExclude: [],
        daysMap: {
          1: 'Mon',
          2: 'Tue',
          3: 'Wed',
          4: 'Thu',
          5: 'Fri',
          6: 'Sat',
          7: 'Sun',
        }
      };
    },
    components: {
    },
    computed: {

    },
    methods: {
      setInitialStep: function (stepName) {
        this.initialStep = stepName;
      },
      skip: function(stepName) {
        // Lets found next step based on initial step.
        var nextStep = '';
        let stepOptions = this.stepOptions[this.initialStep];
          for (let i = 0; i <= stepOptions.length; i++) {
            if (stepOptions[i] === stepName) {
              // Found next step id.
              if (typeof stepOptions[i + 1] !== 'undefined') {
                nextStep = stepOptions[i + 1];
              }
              // This is the last step.
              else {
                // @todo redirect to program search with all parameters.
              }
            }
          }
          // Go to next step.
        if (nextStep) {
          this.$router.push({name: 'af-' + nextStep, query: this.$route.query});
        }
      },
      next: function(stepName) {
        // Lets found next step based on initial step.
        var nextStep = '';
        let stepOptions = this.stepOptions[this.initialStep];
        for (let i = 0; i <= stepOptions.length; i++) {
          if (stepOptions[i] === stepName) {
            // Found next step id.
            if (typeof stepOptions[i + 1] !== 'undefined') {
              nextStep = stepOptions[i + 1];
            }
            // This is the last step.
            else {
              // @todo redirect to program search with all parameters.
            }
          }
        }
        // Go to next step.
        if (nextStep) {
          this.runAjaxRequest();
          this.$router.push({name: 'af-' + nextStep, query: this.$route.query});
        }
      },
      submitSearch: function () {
        window.location.href = this.programSearchUrl + window.location.search;
      },
      startOver: function () {
        var component = this;
        // Reset all filters.
        component.keywords = '';
        component.checkedAges = [];
        component.checkedDays = [];
        component.checkedProgramTypes = [];
        component.checkedCategories = [];
        component.checkedLocations = [];
        component.runAjaxRequest();
        // Go to the homepage.
        component.$router.push({ name: 'home', query: {}});
      },
      cardSelected: function(checkedValues, value) {
        if (typeof value === 'undefined') {
          return false;
        }
        return checkedValues.indexOf(value) != -1;
      },
      updateSearchQuery: function() {
        let component = this;
        this.$router.push({ query: {
          keywords: encodeURIComponent(component.keywords),
          ages: component.checkedAges.join(','),
          program_types: component.checkedProgramTypes.join(','),
          categories: component.checkedCategories.join(','),
          days: component.checkedDays.join(','),
          locations: component.checkedLocations.join(','),
          exclude: component.categoriesExclude.join(',')
        }});
      },
      runAjaxRequest: function() {
        let component = this,
            url = drupalSettings.path.baseUrl + 'af/get-data',
            query = [];

        if (this.checkedAges.length > 0) {
          query.push('ages=' + encodeURIComponent(this.checkedAges.join(',')));
        }

        if (this.checkedDays.length > 0) {
          query.push('days=' + encodeURIComponent(this.checkedDays.join(',')));
        }

        if (this.checkedCategories.length > 0) {
          query.push('categories=' + encodeURIComponent(this.checkedCategories.join(',')));
        }

        if (this.categoriesExclude.length > 0) {
          query.push('exclude=' + encodeURIComponent(this.categoriesExclude.join(',')));
        }

        if (this.checkedLocations.length > 0) {
          query.push('locations=' + encodeURIComponent(this.checkedLocations.join(',')));
        }

        if (query.length > 0) {
          url += '?' + query.join('&');
        }

        component.loading = true;
        jQuery.getJSON(url, function(data) {
          component.table = data;
        }).done(function() {
          component.loading = false;
        });
      },
      categoryIsNotExcluded: function(value) {
        for (var i in this.categoriesExclude) {
          if (this.categoriesExclude[i] == value) {
            return false;
          }
        }
        return true;
      },
      getCategoryNameById: function(value) {
        for (var topLevelCategories in this.activities) {
          for (var category in this.activities[topLevelCategories].value) {
            if (this.activities[topLevelCategories].value[category].value == value) {
              return this.activities[topLevelCategories].value[category].label;
            }
          }
        }
      },
      getCategoryParentNameByChildId: function(value) {
        for (var topLevelCategories in this.activities) {
          for (var category in this.activities[topLevelCategories].value) {
            if (this.activities[topLevelCategories].value[category].value == value) {
              return this.activities[topLevelCategories].label;
            }
          }
        }
      },
      getLocationNameById: function(value) {
        for (var topLevelLocation in this.locations) {
          for (var location in this.locations[topLevelLocation].value) {
            if (this.locations[topLevelLocation].value[location].value == value) {
              return this.locations[topLevelLocation].value[location].label;
            }
          }
        }
      },
      getAgeNameById: function(value) {
        for (var i in this.ages) {
          if (this.ages[i].value == value) {
            return this.ages[i].label;
          }
        }
      },
      getDayNameById: function(value) {
        for (var i in this.days) {
          if (this.days[i].value == value) {
            return this.days[i].label;
          }
        }
      },
      initializeFromGet: function() {
        if (typeof this.$route.query.ages != 'undefined') {
          var checkedAgesGet = decodeURIComponent(this.$route.query.ages);
          if (checkedAgesGet) {
            this.checkedAges = checkedAgesGet.split(',');
          }
        }

        if (typeof this.$route.query.days != 'undefined') {
          var checkedDaysGet = decodeURIComponent(this.$route.query.days);
          if (checkedAgesGet) {
            this.checkedDays = checkedDaysGet.split(',');
          }
        }

        if (typeof this.$route.query.categories != 'undefined') {
          var checkedCategoriesGet = decodeURIComponent(this.$route.query.categories);
          if (checkedCategoriesGet) {
            this.checkedCategories = checkedCategoriesGet.split(',');
          }
        }

        if (typeof this.$route.query.locations != 'undefined') {
          var checkedLocationsGet = decodeURIComponent(this.$route.query.locations);
          if (checkedLocationsGet) {
            this.checkedLocations = checkedLocationsGet.split(',');
          }
        }
      },
    },
    mounted: function() {
      let component = this;

      component.initializeFromGet();

      // Initial run of ajax request.
      component.runAjaxRequest();

      // Listen for events from components.
      component.$on('setInitialStep', function (stepName) {
        component.setInitialStep(stepName);
      });
      component.$on('next', function (stepName) {
        component.next(stepName);
      });
      component.$on('skip', function (stepName) {
        component.skip(stepName);
      });
      component.$on('startOver', function () {
        component.startOver();
      });
      component.$on('submitSearch', function () {
        component.submitSearch();
      });

      // Watchers.
      component.$watch('keywords', function(newValue, oldValue){
        component.updateSearchQuery();
      });
      component.$watch('checkedAges', function(){
        component.updateSearchQuery();
      });
      component.$watch('checkedDays', function(){
        component.updateSearchQuery();
      });
      component.$watch('checkedCategories', function(){
        component.updateSearchQuery();
      });
      component.$watch('checkedLocations', function(){
        component.updateSearchQuery();
      });

      // Get url from paragraph's field.
      component.programSearchUrl = 'OpenY' in window ? window.OpenY.field_prgf_af_results_ref[0]['url'] : '';
      // Get 1/0 from paragraph's field.
      component.hideProgramStep = 'OpenY' in window ? window.OpenY.field_prgf_hide_program_categ[0]['value'] : '';
      // Get 1/0 from paragraph's field.
      component.hideLocationStep = 'OpenY' in window ? window.OpenY.field_prgf_hide_loc_select_step[0]['value'] : '';
      // Get limit category if any.
      if ('OpenY' in window && typeof window.OpenY.field_prgf_af_categ != 'undefined') {
        for (var i in window.OpenY.field_prgf_af_categ) {
          component.limitCategory.push(window.OpenY.field_prgf_af_categ[i]['id']);
          // Hide program selection step if any category has been selected.
          component.hideProgramStep = 1;
          // Pre populate categories.
          component.checkedCategories.push(window.OpenY.field_prgf_af_categ[i]['id']);
          // @todo
          // Make step 1 initial.
          //component.step = 1;
        }
      }
      // Get exclude categories if any.
      if ('OpenY' in window && typeof window.OpenY.field_prgf_af_categ_excl != 'undefined') {
        for (var i in window.OpenY.field_prgf_af_categ_excl) {
          component.categoriesExclude.push(window.OpenY.field_prgf_af_categ_excl[i]['id']);
        }
      }
    }
  }
</script>
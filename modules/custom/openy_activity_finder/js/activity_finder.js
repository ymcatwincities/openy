(function ($) {
  Vue.config.devtools = true;
  if (!$('#activity-finder-app').length) {
    return;
  }

  var router = new VueRouter({
    mode: 'history',
    routes: []
  });

  new Vue({
    el: '#activity-finder-app',
    router: router,
    data: {
      step: 0,
      loading: false,
      isStep1NextDisabled: true,
      isStep2NextDisabled: true,
      isStep3NextDisabled: true,
      keywords: '',
      step_1_query: '',
      step_2_query: '',
      step_3_query: '',
      afResultsRef: '',
      hideProgramStep: 0,
      hideLocationStep: 0,
      total_steps: 3,
      current_step: 0,
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
      checkedAges: [],
      checkedDays: [],
      checkedProgramTypes: [],
      checkedCategories: [],
      checkedLocations: [],
      checkedStep1Filters: '',
      checkedStep2Filters: '',
      checkedStep3Filters: '',
      categories: {}
    },
    methods: {
      toStep: function(s) {
        this.step = s;
        this.current_step = s;
        this.updateStepsViewAll(s);
      },
      skip: function() {
        if (this.step == 3) {
          // Redirect to Search page.
          window.location.pathname = this.afResultsRef;
        }
        else {
          this.step++;
          this.current_step++;
        }
      },
      prev: function() {
        this.step--;
      },
      next: function() {
        if (this.hideLocationStep == 1 && this.hideLocationStep == 1) {
          // Redirect to Search page.
          this.updateCategoriesParam();
          this.updateSearchQuery();
          window.location.pathname = this.afResultsRef;
          return;
        }
        if (this.step == 3) {
          // Redirect to Search page.
          window.location.pathname = this.afResultsRef;
        }
        this.step++;
        this.current_step++;
        if (this.step == 2 && this.hideProgramStep == 1) {
          this.current_step = 2;
          this.step = 3;
        }
        if (this.step == 2 && this.checkedProgramTypes.length === 0) {
          this.current_step = 3;
          this.step = 3;
        }
        if (this.step == 3 && this.hideLocationStep == 1) {
          this.current_step = 2;
          this.step = 3;
        }
        this.updateStepsViewAll(this.step);
        this.runAjaxRequest();
      },
      startOver: function() {
        var component = this;
        router.push({ query: {}});
        component.step = 0;
        component.keywords = '';
        component.checkedAges = [];
        component.checkedDays = [];
        component.checkedProgramTypes = [];
        component.checkedCategories = [];
        component.checkedLocations = [];
        component.checkedStep1Filters = '';
        component.checkedStep2Filters = '';
        component.checkedStep3Filters = '';
        this.runAjaxRequest();
      },
      updateStepsViewAll: function(step) {
        var component = this;
        switch (step) {
          case 1:
            component.step_1_query = window.location.search;
            break;
          case 2:
            this.updateCategoriesParam();
            component.step_2_query = window.location.search;
            break;
          case 3:
            this.updateLocationsParam();
            component.step_3_query = window.location.search;
            break;
        }
      },
      updateCategoriesParam: function() {
        var selectedCategories = [];
        for (i in this.selectedCategories) {
          for (j in this.selectedCategories[i].value) {
            if (typeof(this.selectedCategories[i].value[j].value) !== 'undefined') {
              selectedCategories.push(this.selectedCategories[i].value[j].value);
            }
          }
        }
        this.checkedCategories = selectedCategories;
      },
      updateLocationsParam: function() {
        var selectedLocations = [];
        for (key in this.table.facets.locations) {
          if (typeof(this.table.facets.locations[key].id) !== 'undefined') {
            selectedLocations.push(this.table.facets.locations[key].id);
          }
        }
        this.checkedLocations = selectedLocations;
      },
      updateSearchQuery: function() {
        var component = this;
        router.push({ query: {
          keywords: encodeURIComponent(component.keywords),
          ages: encodeURIComponent(component.checkedAges),
          program_types: encodeURIComponent(component.checkedProgramTypes),
          categories: encodeURIComponent(component.checkedCategories),
          days: encodeURIComponent(component.checkedDays),
          locations: encodeURIComponent(component.checkedLocations)
        }});
      },
      checkFilters: function(step) {
        var component = this,
            filters = [];
        switch (step) {
          case 1:
            component.checkedStep1Filters = '';
            component.isStep1NextDisabled = true;
            if (component.checkedAges.length > 0 ||
              component.checkedDays.length > 0 ||
              component.checkedProgramTypes.length > 0) {

              component.isStep1NextDisabled = false;

              // Map ids to titles.
              for (key in component.checkedAges) {
                if (typeof(component.checkedAges[key]) !== 'function' && $('#af-age-filter-' + component.checkedAges[key])) {
                  filters.push($('#af-age-filter-' + component.checkedAges[key]).parent('label').text());
                }
              }

              // Map ids to titles.
              for (key in component.checkedDays) {
                if (typeof(component.checkedDays[key]) !== 'function' && $('#af-day-filter-' + component.checkedDays[key])) {
                  filters.push($('#af-day-filter-' + component.checkedDays[key]).parent('label').text());
                }
              }

              component.checkedProgramTypes.length > 0 ? filters.push(component.checkedProgramTypes.join(', ')) : '';
              component.checkedStep1Filters = filters.join(', ');
            }
            break;
          case 2:
            component.checkedStep2Filters = '';
            component.isStep2NextDisabled = true;
            if (
              component.checkedCategories.length > 0) {

              component.isStep2NextDisabled = false;
              // Map ids to titles.
              var checkedMapCategories = [];
              for (key in component.checkedCategories) {
                if (typeof(component.checkedCategories[key]) !== 'function' && $('input[value="' + component.checkedCategories[key] + '"]').length !== 0) {
                  checkedMapCategories.push($('input[value="' + component.checkedCategories[key] + '"]').parent('label').text());
                }
              }
              filters.push(checkedMapCategories.join(', '));
              component.checkedStep2Filters = filters.join(', ');
            }
            break;
          case 3:
            component.checkedStep3Filters = '';
            component.isStep3NextDisabled = true;
            if (
              component.checkedLocations.length > 0) {

              component.isStep3NextDisabled = false;

              // Map ids to titles.
              var checkedMapLocations = [];
              for (key in component.checkedLocations) {
                if (typeof(component.checkedLocations[key]) !== 'function' && $('input[value="' + component.checkedLocations[key] + '"]').length !== 0) {
                  checkedMapLocations.push($('input[value="' + component.checkedLocations[key]+'"]').parent('label').find('span').text());
                }
              }
              filters.push(checkedMapLocations.join(', '));
              component.checkedStep3Filters = filters.join(', ');
            }
            break;
        }
      },
      runAjaxRequest: function() {
        var component = this;

        var url = drupalSettings.path.baseUrl + 'af/get-data';

        if (window.location.search !== '') {
          url += window.location.search;
        }

        component.loading = true;
        $.getJSON(url, function(data) {
          component.table = data;
          component.loading = false;
        });
      },
      locationCounter: function(locationId) {
        if (typeof this.table.facets.locations == 'undefined') {
          return 0;
        }
        for (key in this.table.facets.locations) {
          if (this.table.facets.locations[key].id == locationId) {
            return this.table.facets.locations[key].count;
          }
        }
        return 0;
      },
      getLocationsCounter: function(key) {
        if (typeof(this.table.groupedLocations) == 'undefined' || typeof(this.table.groupedLocations[key]) == 'undefined') {
          return 0;
        }
        return this.table.groupedLocations[key].count;
      },
      toggleCardState: function(e) {
        var element = $(e.target);
        if(!element.parents('.openy-card__item').hasClass('selected')) {
          element.parents('.openy-card__item').addClass('selected');
        }
        else {
          element.parents('.openy-card__item').removeClass('selected');
        }
      }
    },
    computed: {
      topLevelCategories: function() {
        var topLevel = [];
        for (key in this.categories) {
          if (this.categories[key].label) {
            topLevel.push(this.categories[key].label);
          }
        }
        return topLevel;
      },
      selectedCategories: function() {
        var selected = [];
        for (key in this.categories) {
          if (this.checkedProgramTypes.indexOf(this.categories[key].label) != -1) {
            selected.push(this.categories[key]);
          }
        }
        return selected;
      }
    },
    mounted: function() {
      var component = this;
      component.categories = drupalSettings.activityFinder.categories;
      this.runAjaxRequest();
      component.$watch('keywords', function(){
        component.updateSearchQuery();
      });
      component.$watch('checkedAges', function(){
        component.updateSearchQuery();
        component.checkFilters(1);
      });
      component.$watch('checkedDays', function(){
        component.updateSearchQuery();
        component.checkFilters(1);
      });
      component.$watch('checkedProgramTypes', function(){
        component.updateSearchQuery();
        component.checkFilters(1);
      });
      component.$watch('checkedCategories', function(){
        component.updateSearchQuery();
        component.checkFilters(2);
      });
      component.$watch('checkedLocations', function(){
        component.updateSearchQuery();
        component.checkFilters(3);
      });
      // Get url from paragraph's field.
      component.afResultsRef = $('.field-prgf-af-results-ref a').attr('href');
      // Get 1/0 from paragraph's field.
      component.hideProgramStep = $('.field-prgf-hide-program-categ').text();
      // Get 1/0 from paragraph's field.
      component.hideLocationStep = $('.field-prgf-hide-loc-select-step').text();
      if (this.hideProgramStep == 1) {
        this.total_steps--;
      }
      if (this.hideLocationStep == 1) {
        this.total_steps--;
      }
    },
    delimiters: ["${","}"]
  });
})(jQuery);

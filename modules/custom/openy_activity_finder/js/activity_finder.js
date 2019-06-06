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
      isSearchSubmitDisabled: true,
      keywords: '',
      step_1_query: '',
      step_2_query: '',
      step_3_query: '',
      afResultsRef: '',
      hideProgramStep: 0,
      hideLocationStep: 0,
      total_steps: 3,
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
      rebuildBreadcrumbs: 5,
      categories: {},
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
    },
    methods: {
      initializeFromGet: function() {
        var component = this;
        component.categories = drupalSettings.activityFinder.categories;

        // @TODO Replace this with proper displaying the step.
        if (typeof this.$route.query.step != 'undefined') {
          var stepGet = decodeURIComponent(this.$route.query.step);
          if (stepGet) {
            var i;
            for (i = 0; i < stepGet; i++) {
              this.next(false);
            }
          }
        }

        component.runAjaxRequest();

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

        if (typeof this.$route.query.program_types != 'undefined') {
          var checkedProgramTypesGet = decodeURIComponent(this.$route.query.program_types);
          if (checkedProgramTypesGet) {
            this.checkedProgramTypes = checkedProgramTypesGet;
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
      toStep: function(s) {
        this.step = s;
        this.updateStepsViewAll(s);
      },
      skip: function() {
        if (this.step == 3) {
          // Redirect to Search page.
          let redirectUrl = this.afResultsRef + window.location.search;
          // Include all categories from checked program types if any.
          var selectedCategories = this.getSelectedCategories();
          if (selectedCategories.length > 0) {
            selectedCategories = selectedCategories.join(',');
            redirectUrl = redirectUrl.replace('categories=', 'categories=' + selectedCategories);
          }
          window.location.href = redirectUrl;
        }
        else {
          this.next();
        }
      },
      prev: function() {
        this.step--;
      },
      next: function(run_ajax = true) {
        // Steps are:
        // 1 -- age, day, program types
        // 2 -- categories
        // 3 -- locations

        // If we hide both Categories and Location steps -- go to Results page.
        if (this.hideLocationStep == 1 && this.hideProgramStep == 1) {
          // Redirect to Search page.
          let redirectUrl = this.afResultsRef + window.location.search;
          // Include all categories from checked program types if any.
          if (this.cleanCheckedCategories.length == 0 && this.selectedCategories.length != 0) {
            let selectedCategories = this.getSelectedCategories();
            if (selectedCategories.length > 0) {
              selectedCategories = selectedCategories.join(',');
              redirectUrl = redirectUrl.replace('categories=', 'categories=' + selectedCategories);
            }
          }
          window.location.href = redirectUrl;
        }

        // We reached third step already. Next one is Results.
        if (this.step == 3 && run_ajax) {
          // Redirect to Search page.
          let redirectUrl = this.afResultsRef + window.location.search;
          // Include all categories from checked program types if any.
          if (this.cleanCheckedCategories.length == 0 && this.selectedCategories.length != 0) {
            let selectedCategories = this.getSelectedCategories();
            if (selectedCategories.length > 0) {
              selectedCategories = selectedCategories.join(',');
              redirectUrl = redirectUrl.replace('categories=', 'categories=' + selectedCategories);
            }
          }
          window.location.href = redirectUrl;
        }

        // If we supposed to move to Locations step but it should be hidden -- redirect to Results.
        if (this.step == 2 && this.hideLocationStep == 1) {
          // Redirect to Search page.
          let redirectUrl = this.afResultsRef + window.location.search;
          // Include all categories from checked program types if any.
          if (this.cleanCheckedCategories.length == 0 && this.selectedCategories.length != 0) {
            let selectedCategories = this.getSelectedCategories();
            if (selectedCategories.length > 0) {
              selectedCategories = selectedCategories.join(',');
              redirectUrl = redirectUrl.replace('categories=', 'categories=' + selectedCategories);
            }
          }
          window.location.href = redirectUrl;
        }

        this.step++;

        // If we moved from first step and we hide Categories step -- redirect to Locations.
        if (this.step == 2 && this.hideProgramStep == 1) {
          this.next(run_ajax);
        }

        // If we didn't select any Program Types no need to show Categories. So skip the step.
        if (this.step == 2 && this.cleanCheckedProgramTypes.length === 0) {
          this.next(run_ajax);
        }

        this.updateStepsViewAll(this.step);
        if (run_ajax) {
          this.runAjaxRequest();
        }
      },
      submitSearch: function() {
        // Redirect to Search page.
        window.location.href = this.afResultsRef + window.location.search;
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
        var component = this,
            selectedCategories = component.getSelectedCategories();

        switch (step) {
          case 1:
            component.step_1_query = window.location.search;
            break;
          case 2:
            component.step_2_query = window.location.search;
            // Include all categories from checked program types if any.
            if (selectedCategories.length > 0) {
              selectedCategories = selectedCategories.join(',');
              component.step_2_query = component.step_2_query.replace('categories=', 'categories=' + selectedCategories);
            }
            break;
          case 3:
            component.step_3_query = window.location.search;
            // If no Category was selected on Step 2.
            if (this.cleanCheckedCategories.length == 0 && this.selectedCategories.length != 0) {
              this.checkedCategories = this.getSelectedCategories();

              // Include all categories from checked program types if any.
              if (selectedCategories.length > 0) {
                selectedCategories = selectedCategories.join(',');
                component.step_3_query = component.step_3_query.replace('categories=', 'categories=' + selectedCategories);
              }
            }

            break;
        }
        window.scrollTo(0, 0);
      },
      // Returns categories from selected top level Categories.
      getSelectedCategories: function() {
        var selectedCategories = [];
        for (i in this.selectedCategories) {
          for (j in this.selectedCategories[i].value) {
            if (typeof(this.selectedCategories[i].value[j].value) !== 'undefined') {
              selectedCategories.push(this.selectedCategories[i].value[j].value);
            }
          }
        }
        return selectedCategories;
      },
      updateSearchQuery: function() {
        var component = this;
        router.push({ query: {
          step: component.step,
          keywords: encodeURIComponent(component.keywords),
          ages: component.cleanCheckedAges.join(','),
          program_types: component.cleanCheckedProgramTypes.join(','),
          categories: component.cleanCheckedCategories.join(','),
          days: component.cleanCheckedDays.join(','),
          locations: component.cleanCheckedLocations.join(','),
          exclude: component.categoriesExclude.join(',')
        }});
      },
      matchHeight: function() {
        maxHeight = [];
        $('.activity-finder__step_content .row', document).each(function(index) {
          cards = $(this).find('.openy-card__item label');
          for (i = 0; i < cards.length; i++) {
            card = cards[i];
            if (!maxHeight[index] || $(card).height() > maxHeight[index]) {
              maxHeight[index] = $(card).height();
            }
          }
        });
        $('.activity-finder__step_content .row', document).each(function(index) {
          $(this).find('.openy-card__item label').height(maxHeight[index]);
        });

      },
      runAjaxRequest: function() {
        var component = this,
          url = drupalSettings.path.baseUrl + 'af/get-data',
          query = [];

        if (this.cleanCheckedAges.length > 0) {
          query.push('ages=' + encodeURIComponent(this.cleanCheckedAges.join(',')));
        }

        if (this.cleanCheckedDays.length > 0) {
          query.push('days=' + encodeURIComponent(this.cleanCheckedDays.join(',')));
        }

        if (this.cleanCheckedProgramTypes.length > 0) {
          query.push('program_types=' + encodeURIComponent(this.cleanCheckedProgramTypes.join(',')));
        }

        if (this.cleanCheckedCategories.length > 0) {
          query.push('categories=' + encodeURIComponent(this.cleanCheckedCategories.join(',')));
        }

        if (this.categoriesExclude.length > 0) {
          query.push('exclude=' + encodeURIComponent(this.categoriesExclude.join(',')));
        }

        if (this.cleanCheckedLocations.length > 0) {
          query.push('locations=' + encodeURIComponent(this.cleanCheckedLocations.join(',')));
        }

        if (query.length > 0) {
          url += '?' + query.join('&');
        }

        component.loading = true;
        $.getJSON(url, function(data) {
          component.table = data;
          if (typeof component.$route.query.step !== 'undefined') {
            component.step = component.$route.query.step * 1;
          }
        }).done(function() {
          component.loading = false;
        });
      },
      locationCounter: function(locationId) {
        if (typeof this.locationCounters[locationId] == 'undefined') {
          return 0;
        }
        return this.locationCounters[locationId];
      },
      getLocationsCounter: function(key) {
        if (typeof(this.table.groupedLocations) == 'undefined' || typeof(this.table.groupedLocations[key]) == 'undefined') {
          return 0;
        }
        return this.table.groupedLocations[key].count;
      },
      locationsSelected: function(e) {
        // Check if locations from group are checked and return its counter.
        let count = 0;
        for (i in this.table.groupedLocations) {
          if (this.table.groupedLocations[i].label === e) {
            for (j in this.table.groupedLocations[i].value) {
              let value = this.table.groupedLocations[i].value[j].value.toString();
              if (this.checkedLocations.indexOf(value) != -1) {
                count++;
              }
            }
          }
        }
        return count;
      },
      cardSelected: function(checkedValues, value) {
        if (typeof value === 'undefined') {
          return false;
        }
        value = value.toString();
        return checkedValues.indexOf(value) != -1;
      },
      jsUcfirst: function(value) {
        return value.charAt(0).toUpperCase() + value.slice(1);
      },
      categoryIsNotExcluded: function(value) {
        for (var i in this.categoriesExclude) {
          // Do not use strict comparison === here in order to support string values.
          if (this.categoriesExclude[i] == value) {
            return false;
          }
        }
        return true;
      },
    },
    computed: {
      cleanCheckedAges: function() {
        return this.checkedAges.filter(function(word){ return word; });
      },
      cleanCheckedDays: function() {
        return this.checkedDays.filter(function(word){ return word; });
      },
      cleanCheckedProgramTypes: function() {
        var checkedProgramTypes = this.checkedProgramTypes;
        if (typeof this.checkedProgramTypes == 'string') {
          checkedProgramTypes = [ this.checkedProgramTypes ];
        }
        return checkedProgramTypes.filter(function(word){ return word; });
      },
      cleanCheckedCategories: function() {
        var checkedCategories = this.checkedCategories;
        if (typeof this.checkedCategories === 'string') {
          checkedCategories = [this.checkedCategories];
        }
        return checkedCategories.filter(function(word){ return word; });
      },
      cleanCheckedLocations: function() {
        return this.checkedLocations.filter(function(word){ return word; });
      },
      isStep1NextDisabled: function() {
        return this.cleanCheckedAges.length === 0 && this.cleanCheckedDays.length === 0 && this.cleanCheckedProgramTypes.length === 0;
      },
      isStep2NextDisabled: function() {
        return this.cleanCheckedCategories.length === 0;
      },
      isStep3NextDisabled: function() {
        return this.cleanCheckedLocations.length === 0;
      },

      locationCounters: function() {
        var counters = [];
        if (typeof this.table.facets.locations == 'undefined') {
          return counters;
        }
        for (key in this.table.facets.locations) {
          counters[this.table.facets.locations[key].id] = this.table.facets.locations[key].count;
        }

        return counters;
      },
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
      },
      filtersBreadcrumbs1: function() {
        var filter = [];
        for (key in this.cleanCheckedAges) {
          var months = this.cleanCheckedAges[key];
          var monthsStr = '';
          if (months < 24) {
            monthsStr = months + 'mo';
          }
          else {
            monthsStr = months / 12 + 'y';
          }
          filter.push(monthsStr);
        }

        for (key in this.cleanCheckedDays) {
          var day = this.cleanCheckedDays[key];
          filter.push(this.daysMap[day]);
        }

        for (key in this.cleanCheckedProgramTypes) {
          filter.push(this.cleanCheckedProgramTypes[key]);
        }

        // Need it here so breadcrumbs will be rebuild after DOM is rendered.
        // see mounted() call.
        this.rebuildBreadcrumbs;

        return filter.join(', ');
      },
      filtersBreadcrumbs2: function() {
        var filter = [];

        for (key in this.cleanCheckedCategories) {
          var label = $('#af-filter-category-' + this.cleanCheckedCategories[key]).data('label');
          filter.push(label);
        }

        // Need it here so breadcrumbs will be rebuild after DOM is rendered.
        // see mounted() call.
        this.rebuildBreadcrumbs;

        return filter.join(', ');
      },
      filtersBreadcrumbs3: function() {
        var filter = [];

        for (key in this.cleanCheckedLocations) {
          var label = $('#af-filter-location-' + this.cleanCheckedLocations[key]).data('label');
          filter.push(label);
        }

        // Need it here so breadcrumbs will be rebuild after DOM is rendered.
        // see mounted() call.
        this.rebuildBreadcrumbs;

        return filter.join(', ');
      }
    },
    mounted: function() {
      var component = this;
      component.initializeFromGet();
      component.$watch('step', function(){
        component.updateSearchQuery();
      });
      component.$watch('keywords', function(newValue, oldValue){
        component.isSearchSubmitDisabled = newValue === '' ?  true : false;
        component.updateSearchQuery();
      });
      component.$watch('checkedAges', function(){
        component.updateSearchQuery();
      });
      component.$watch('checkedDays', function(){
        component.updateSearchQuery();
      });
      component.$watch('checkedProgramTypes', function(){
        component.updateSearchQuery();
      });
      component.$watch('checkedCategories', function(){
        component.updateSearchQuery();
      });
      component.$watch('checkedLocations', function(){
        component.updateSearchQuery();
      });
      // Get url from paragraph's field.
      component.afResultsRef = 'OpenY' in window ? window.OpenY.field_prgf_af_results_ref[0]['url'] : '';
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
          // Make step 1 initial.
          component.step = 1;
        }
      }
      // Get exclude categories if any.
      if ('OpenY' in window && typeof window.OpenY.field_prgf_af_categ_excl != 'undefined') {
        for (var i in window.OpenY.field_prgf_af_categ_excl) {
          component.categoriesExclude.push(window.OpenY.field_prgf_af_categ_excl[i]['id']);
        }
      }
      if (this.hideProgramStep == 1) {
        this.total_steps--;
      }
      if (this.hideLocationStep == 1) {
        this.total_steps--;
      }

      // We need to rebuild breadcrumbs because they depend on DOM structure
      // and it is being rendered by javascript. Once it is rendered, we can
      // run our jQuery queries to get labels from input elements.
      var component = this;

      // Instead of fixed time we check if locations checkboxes got displayed.
      var checkExist = setInterval(function() {
        if ($('#locations-accordion-group input').length) {
          component.rebuildBreadcrumbs = 1;
          clearInterval(checkExist);
        }
      }, 200);
    },
    watch: {
      // Call again the method if the route changes.
      '$route': function() {

        // If for some reason steps start increasing infinitely it simply hangs
        // the browser. So we have a little protection here.
        if (this.step > 5) {
          console.log(this.step, 'FATAL');
          return;
        }

        var stepGet = decodeURIComponent(this.$route.query.step);

        // @TODO Make transition between step smoother so we do not need
        // to run function next() multiple times.
        if (stepGet != this.step) {
          this.step = 0;
          var i;
          for (i = 0; i < stepGet; i++) {
            this.next(false);
          }
        }

        if (typeof this.$route.query.ages != 'undefined') {
          var checkedAgesGet = decodeURIComponent(this.$route.query.ages);
          if (JSON.stringify(checkedAgesGet.split(',')) != JSON.stringify(this.checkedAges)) {
            this.checkedAges = checkedAgesGet.split(',');
          }
        }

        if (typeof this.$route.query.ages != 'undefined') {
          var checkedDaysGet = decodeURIComponent(this.$route.query.days);
          if (JSON.stringify(checkedDaysGet.split(',')) != JSON.stringify(this.checkedDays)) {
            this.checkedDays = checkedDaysGet.split(',');
          }
        }

        if (typeof this.$route.query.program_types != 'undefined') {
          var checkedProgramTypesGet = decodeURIComponent(this.$route.query.program_types);
          if (JSON.stringify(checkedProgramTypesGet.split(',')) != JSON.stringify(this.checkedProgramTypes)) {
            this.checkedProgramTypes = checkedProgramTypesGet.split(',');
          }
        }

        if (typeof this.$route.query.categories != 'undefined') {
          var checkedCategoriesGet = decodeURIComponent(this.$route.query.categories);
          if (JSON.stringify(checkedCategoriesGet.split(',')) != JSON.stringify(this.checkedCategories)) {
            this.checkedCategories = checkedCategoriesGet.split(',');
          }
        }

        if (typeof this.$route.query.locations != 'undefined') {
          var checkedLocationsGet = decodeURIComponent(this.$route.query.locations);
          if (JSON.stringify(checkedLocationsGet.split(',')) != JSON.stringify(this.checkedLocations)) {
            this.checkedLocations = checkedLocationsGet.split(',');
          }
        }
      }
    },
    updated: function(){
      if (this.step === 2) {
        this.matchHeight();
      }
    },
    delimiters: ["${","}"]
  });
})(jQuery);

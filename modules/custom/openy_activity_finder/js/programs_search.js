(function ($) {
  Vue.config.devtools = true;

  if (!$('.schedule-dashboard__wrapper').length) {
    return;
  }

  var router = new VueRouter({
    mode: 'history',
    routes: []
  });

  Vue.component('sidebar-filter', {
    props: ['title', 'id', 'options', 'default', 'type', 'expanded', 'hide_label'],
    data: function() {
      return {
        checkboxes: [],
        checked: [],
        expanded_checkboxes: {},
        dependencies: {},
      }
    },
    created: function() {
      this.checkboxes = JSON.parse(this.options);
      if (typeof this.default !== 'undefined') {
        this.checked = this.default.split(',').map(function (item) {
          if (isNaN(item)) {
            return item;
          }
          return +item;
        });
      }
      for (var i in this.checkboxes) {
        checkbox = this.checkboxes[i];
        if (typeof checkbox == 'object') {
          this.dependencies[checkbox.label] = [];
          for (var k in checkbox.value) {
            var item = checkbox.value[k];
            var value = isNaN(item.value) ? item.value : +item.value;
            this.dependencies[checkbox.label].push(value);
          }
        }
      }
    },
    watch: {
      checked: function(values) {
        // Some of the values could be empty. Clean them up.
        var cleanValues = [];
        for (key in values) {
          if (values[key] != '') {
            cleanValues.push(values[key]);
          }
        }
        this.$emit('updated-values', cleanValues);
      }
    },
    methods: {
      clear: function() {
        this.checked = [];
      },
      getId: function(string) {
        return string.replace(/^[0-9a-zA-Z]/g, '-');
      },
      getOption: function(data) {
        if (!isNaN(data.value)) {
          return data.value * 1;
        }
        return data.value;
      },
      getLabel: function(data) {
        return data.label;
      },
      collapseGroup: function(checkbox) {
        var label = this.getLabel(checkbox);
        return typeof this.expanded_checkboxes[label] == 'undefined' || this.expanded_checkboxes[label] == false;
      },
      collapseAllGroups: function(checkbox) {
        var label = this.getLabel(checkbox);
        // Close all other expanded groups.
        for (var i in this.expanded_checkboxes) {
          if (i !== label && this.expanded_checkboxes[i] !== false) {
            this.expanded_checkboxes[i] = false;
          }
        }
      },
      checkboxIsDisabled: function(title, value) {
        var component = this.$parent;
        switch (title) {
          case "Age":
            if (typeof component.facets.static_age_filter !== 'undefined') {
              for (var i in component.facets.static_age_filter) {
                if (component.facets.static_age_filter[i]['filter'] == value && component.facets.static_age_filter[i]['count'] === 0) {
                  return true;
                }
              }
            }
            break;
          case "Days":
            if (
              typeof component.daysMap[value] !== 'undefined' &&
              typeof component.facets.days_of_week !== 'undefined' &&
              typeof component.facets.days_of_week[value] !== 'undefined' &&
              typeof component.facets.days_of_week[value]['filter'] !== 'undefined'
            ) {
              for (var i in component.facets.days_of_week) {
                if (component.facets.days_of_week[i]['filter'] == component.daysMap[value] && component.facets.days_of_week[i]['count'] === 0) {
                  return true;
                }
              }
            }
            break;
        }
        return false;
      },
      checkboxIsExcluded: function(title, value) {
        var component = this.$parent;
        switch (title) {
          case "Category":
            if (component.categoriesExcluded.length > 0) {
              for (var i in component.categoriesExcluded) {
                if (component.categoriesExcluded[i] == value) {
                  return true;
                }
              }
            }
            break;
        }
        return false;
      },
      groupStatus: function(value) {
        if (typeof this.dependencies[value] == 'undefined') {
          return false;
        }

        var foundChecked = false;
        var foundUnChecked = false;
        for (var i in this.dependencies[value]) {
          var val = this.dependencies[value][i];
          if (!isNaN(val)) {
            val = +val;
          }
          if (this.checked.indexOf(val) != -1) {
            foundChecked = true;
          }
          else {
            foundUnChecked = true;
          }
        }

        if (foundChecked && foundUnChecked) {
          return 'partial';
        }

        if (foundChecked) {
          return 'all';
        }
        else {
          return 'none';
        }
      },
      groupCounter: function(group) {
        var counter = 0;
        if (typeof group !== 'undefined' && typeof this.dependencies[group] !== 'undefined') {
          for (var i in this.dependencies[group]) {
            for (var j in this.checked) {
              if (this.dependencies[group][i] == this.checked[j]) {
                counter ++;
              }
            }
          }
        }
        else {
          counter = this.checked.length;
          for (var k in this.checked) {
            if (this.checked[k] == 0) {
              counter--;
            }
          }
        }
        return counter;
      },
      selectDependent: function(value) {
        if (typeof this.dependencies[value] == 'undefined') {
          return false;
        }
        var removeValue = (this.groupStatus(value) == 'all' || this.groupStatus(value) == 'partial');
        for (var i in this.dependencies[value]) {
          var key = this.checked.indexOf(this.dependencies[value][i]);
          if (typeof this.dependencies[value][i] != 'string' && typeof this.dependencies[value][i] != 'number') {
            continue;
          }
          // If we need to add and it was not checked yet.
          if (key == -1 && !removeValue) {
            var setVal = this.dependencies[value][i];
            if (!isNaN(setVal)) {
              setVal = +setVal;
            }
            Vue.set(this.checked, this.checked.length, setVal);
          }
          // If already checked but we need to uncheck.
          if (key != -1 && removeValue) {
            for (let k = 0; k < this.dependencies[value].length; k++) {
              for (let j = 0; j < this.checked.length; j++) {
                if (this.checked[j] == this.dependencies[value][k]) {
                  this.checked.splice(this.checked.indexOf(this.dependencies[value][k]), 1);
                }
                if (this.checked[j] == this.dependencies[value][k].toString()) {
                  this.checked.splice(this.checked.indexOf(this.dependencies[value][k].toString()), 1);
                }
              }
            }
          }
        }
      }
    },
    template: '<div class="form-group-wrapper">\n' +
    '                <label v-if="!hide_label" v-on:click="expanded = !expanded">\n' +
    '                 {{ title }}\n' +
    '                  <small v-show="groupCounter() > 0" class="badge">{{ groupCounter() }}</small>'+
    '                  <i v-if="expanded" class="fa fa-minus-circle minus ml-auto" aria-hidden="true"></i>\n' +
    '                  <i v-if="!expanded" class="fa fa-plus-circle plus ml-auto" aria-hidden="true"></i>\n' + //checked.indexOf(getOption(checkbox)) != -1
    '                </label>\n' +
    '                <div v-bind:class="[type]">\n' +
    '                  <div v-for="checkbox in checkboxes" class="checkbox-wrapper" ' +
    '                     v-show="type != \'tabs\' || expanded"' +
    '                     v-bind:class="{\'col-xs-4 col-sm-2 col-md-4 col-4\': type == \'tabs\', \'disabled\': checkboxIsDisabled(title, getOption(checkbox))}">' +
    // No parent checkbox.
    '                    <input v-if="typeof getOption(checkbox) != \'object\'" v-show="expanded" type="checkbox" v-bind:disabled="checkboxIsExcluded(title, getOption(checkbox))" v-model="checked" :value="getOption(checkbox)" :id="\'checkbox-\' + id + \'-\' + getOption(checkbox)">\n' +
    '                    <label v-if="typeof getOption(checkbox) != \'object\'" v-show="expanded" :for="\'checkbox-\' + id + \'-\' + getOption(checkbox)">{{ getLabel(checkbox) }}</label>\n' +
    // Locations with sub-locations/branches.
    '                    <div v-if="typeof getOption(checkbox) == \'object\'">' +
    '                       <a v-if="typeof getOption(checkbox) == \'object\' && expanded" v-on:click.stop.prevent="collapseAllGroups(checkbox);Vue.set(expanded_checkboxes, getLabel(checkbox), true);" href="#" v-bind:class="{\'d-flex checkbox-toggle-subset\': true, \'hidden\': !collapseGroup(checkbox)}">' +
    '                         <label>{{ getLabel(checkbox) }} <small v-show="groupCounter(getLabel(checkbox)) > 0" class="badge">{{ groupCounter(getLabel(checkbox)) }}</small></label>\n' +
    '                         <i class="fa fa-plus-circle plus ml-auto" aria-hidden="true"></i>' +
    '                       </a>' +
    '                       <a v-show="!collapseGroup(checkbox)" v-if="typeof getOption(checkbox) == \'object\' && expanded"  v-on:click.stop.prevent="expanded_checkboxes[getLabel(checkbox)] = false" href="#" v-bind:class="{\'d-flex checkbox-toggle-subset\': true, \'hidden\': collapseGroup(checkbox)}">' +
    '                         <label>{{ getLabel(checkbox) }} <small v-show="groupCounter(getLabel(checkbox)) > 0" class="badge">{{ groupCounter(getLabel(checkbox)) }}</small></label>\n' +
    '                         <i class="fa fa-minus-circle minus ml-auto" aria-hidden="true"></i>' +
    '                       </a>' +
    '                    </div>' +
    '                    <div v-if="typeof getOption(checkbox) == \'object\'" v-for="checkbox2 in getOption(checkbox)" class="checkbox-wrapper">\n' +
    '                      <div v-if="!checkboxIsExcluded(title, getOption(checkbox2))">' +
    '                       <input v-if="expanded && !collapseGroup(checkbox)" type="checkbox" v-model="checked" :value="getOption(checkbox2)" :id="\'checkbox-\' + id + \'-\' + getOption(checkbox2)">\n' +
    '                       <label v-if="expanded && !collapseGroup(checkbox)" :for="\'checkbox-\' + id + \'-\' + getOption(checkbox2)">{{ getLabel(checkbox2) }}</label>\n' +
    '                      </div>\n' +
    '                    </div>\n' +
    '                  </div>\n' +
    '                </div>\n' +
    '              </div>'
  });

  // Does not yet support flat list of radios. Only two level as for Daxko location.
  Vue.component('sidebar-filter-single', {
    props: ['title', 'id', 'options', 'default', 'type', 'hide_label'],
    data: function() {
      return {
        radios: [],
        checked: [],
        expanded: true,
        expanded_checkboxes: {},
        dependencies: {},
      }
    },
    created: function() {
      this.radios = JSON.parse(this.options);
      if (typeof this.default !== 'undefined') {
        this.checked = this.default;
      }
      for (var i in this.radios) {
        radio = this.radios[i];
        if (typeof radio == 'object') {
          this.dependencies[radio.label] = [];
          for (var k in radio.value) {
            var item = radio.value[k];
            this.dependencies[radio.label].push(item.value);
          }
        }
      }
    },
    watch: {
      checked: function(values) {
        this.$emit('updated-values', [values]);
      }
    },
    methods: {
      clear: function() {
        this.checked = '';
      },
      getId: function(string) {
        return string.replace(/^[0-9a-zA-Z]/g, '-');
      },
      getOption: function(data) {
        return data.value;
      },
      getLabel: function(data) {
        return data.label;
      },
      collapseGroup: function(checkbox) {
        var label = this.getLabel(checkbox);
        return typeof this.expanded_checkboxes[label] == 'undefined' || this.expanded_checkboxes[label] == false;
      },
      collapseAllGroups: function(checkbox) {
        var label = this.getLabel(checkbox);
        // Close all other expanded groups.
        for (var i in this.expanded_checkboxes) {
          if (i !== label && this.expanded_checkboxes[i] !== false) {
            this.expanded_checkboxes[i] = false;
          }
        }
      },
      groupCounter: function(group) {
        var counter = 0;
        if (typeof group !== 'undefined' && typeof this.dependencies[group] !== 'undefined') {
          for (var i in this.dependencies[group]) {
            for (var j in this.checked) {
              if (this.dependencies[group][i] == this.checked) {
                counter = 1;
              }
            }
          }
        }
        return counter;
      }
    },
    template: '<div class="form-group-wrapper">\n' +
    '                <label v-if="!hide_label" v-on:click="expanded = !expanded">\n' +
    '                 {{ title }}\n' +
    '                  <i v-if="expanded" class="fa fa-minus-circle minus" aria-hidden="true"></i>\n' +
    '                  <i v-if="!expanded" class="fa fa-plus-circle plus" aria-hidden="true"></i>\n' +
    '                </label>\n' +
    '                <div class="checkboxes">\n' +
    '                  <div v-for="radio in radios" class="checkbox-wrapper" ' +
    '                     v-show="type != \'tabs\' || expanded || checked.indexOf(getOption(radio)) != -1">' +
    '                    <div v-if="typeof getOption(radio) == \'object\'">' +
    '                       <a v-if="typeof getOption(radio) == \'object\' && expanded" v-on:click.stop.prevent="collapseAllGroups(radio);Vue.set(expanded_checkboxes, getLabel(radio), true);" href="#" v-bind:class="{\'d-flex checkbox-toggle-subset\': true, \'hidden\': !collapseGroup(radio)}">' +
    '                         <label>{{ getLabel(radio) }} <small v-show="groupCounter(getLabel(radio)) > 0" class="badge">{{ groupCounter(getLabel(radio)) }}</small></label>\n' +
    '                         <i class="fa fa-plus-circle plus ml-auto" aria-hidden="true"></i>' +
    '                       </a>' +
    '                       <a v-if="typeof getOption(radio) == \'object\' && expanded" v-on:click.stop.prevent="expanded_checkboxes[getLabel(radio)] = false;" href="#" v-bind:class="{\'d-flex checkbox-toggle-subset\': true, \'hidden\': collapseGroup(radio)}">' +
    '                         <label>{{ getLabel(radio) }} <small v-show="groupCounter(getLabel(radio)) > 0" class="badge">{{ groupCounter(getLabel(radio)) }}</small></label>\n' +
    '                         <i class="fa fa-minus-circle minus ml-auto" aria-hidden="true"></i>' +
    '                       </a>' +
    '                    </div>' +
    '                    <div v-if="typeof getOption(radio) == \'object\'" v-for="radio2 in getOption(radio)" class="checkbox-wrapper radio-wrapper">\n' +
    '                      <input v-if="expanded && !collapseGroup(radio)" type="radio" v-model="checked" :value="getOption(radio2)" :id="\'radio-\' + id + \'-\' + getOption(radio2)">\n' +
    '                      <label v-if="expanded && !collapseGroup(radio)" :for="\'radio-\' + id + \'-\' + getOption(radio2)">{{ getLabel(radio2) }}</label>\n' +
    '                    </div>\n' +
    '                  </div>\n' +
    '                </div>\n' +
    '              </div>'
  });

  // Retrieve the data via vue.js.
  new Vue({
    el: '#app',
    router: router,
    data: {
      table: {},
      loading: false,
      count: '',
      facets: [],
      pages: {},
      pager_info: {},
      current_page: 1,
      activeClass: 'active',
      keywords: '',
      locations: [],
      ages: [],
      days: [],
      categories: [],
      categoriesExcluded: [],
      categoriesLimit: [],
      sort: '',
      moreInfoPopupLoading: false,
      runningClearAllFilters: false,
      afPageRef: '',
      no_results: 0,
      alternativeCriteria: '',
      isSpotsAvailableDisabled: drupalSettings.activityFinder.is_spots_available_disabled,
      daysMap: {
        1: 'monday',
        2: 'tuesday',
        3: 'wednesday',
        4: 'thursday',
        5: 'friday',
        6: 'saturday',
        7: 'sunday',
      },
      locationPopup: {
        address: '',
        email: '',
        phone: '',
        title: '',
        days: []
      },
      availabilityPopup: {
        note: '',
        status: '',
        price: '',
        link: '',
        spots_available: '',
      },
      moreInfoPopup: {
        name: '',
        description: '',
        price: '',
        ages: '',
        gender: '',
        dates: '',
        weeks: '',
        schedule: '',
        location_name: '',
        location_address: '',
        location_phone: '',
        availability_status: '',
        availability_note: '',
        link: '',
        learn_more: '',
        more_results: ''
      }
    },
    created: function() {
      var component = this;

      if (typeof this.$route.query.locations != 'undefined') {
        var locationsGet = decodeURIComponent(this.$route.query.locations);
        for (let i = 0; i < locationsGet.length; i++) {
          locationsGet[i] = +locationsGet[i];
        }
        if (locationsGet) {
          this.locations = locationsGet.split(',');
        }
      }

      if (typeof this.$route.query.categories != 'undefined') {
        var categoriesGet = decodeURIComponent(this.$route.query.categories);
        for (let i = 0; i < categoriesGet.length; i++) {
          categoriesGet[i] = +categoriesGet[i];
        }
        if (categoriesGet) {
          this.categories = categoriesGet.split(',');
        }
      }

      if (typeof this.$route.query.exclude != 'undefined') {
        var excludeCategoriesGet = decodeURIComponent(this.$route.query.exclude);
        for (let i = 0; i < excludeCategoriesGet.length; i++) {
          excludeCategoriesGet[i] = +excludeCategoriesGet[i];
        }
        if (excludeCategoriesGet) {
          this.categoriesExcluded = excludeCategoriesGet.split(',');
        }
      }

      if (typeof this.$route.query.ages != 'undefined') {
        var agesGet = decodeURIComponent(this.$route.query.ages);
        if (agesGet) {
          this.ages = agesGet.split(',');
        }
      }

      if (typeof this.$route.query.days != 'undefined') {
        var daysGet = decodeURIComponent(this.$route.query.days);
        if (daysGet) {
          this.days = daysGet.split(',');
        }
      }

      if (typeof this.$route.query.keywords != 'undefined') {
        var keywordsGet = decodeURIComponent(this.$route.query.keywords);
        if (keywordsGet) {
          this.keywords = keywordsGet;
        }
      }

      this.runAjaxRequest();

      component.afPageRef = 'OpenY' in window && typeof window.OpenY.field_prgf_af_page_ref !== 'undefined' && typeof window.OpenY.field_prgf_af_page_ref[0] !== 'undefined' ? window.OpenY.field_prgf_af_page_ref[0]['url'] : '';

      // We add watchers dynamically otherwise initially there will be
      // up to three requests as we are changing values while initializing
      // from GET query parameters.
      component.$watch('locations', function(newValue, oldValue){
        newValue = component.arrayFilter(newValue);
        oldValue = component.arrayFilter(oldValue);
        if (!component.runningClearAllFilters && JSON.stringify(newValue) !== JSON.stringify(oldValue)) {
          component.runAjaxRequest();
        }
      });
      component.$watch('categories', function(newValue, oldValue){
        newValue = component.arrayFilter(newValue);
        oldValue = component.arrayFilter(oldValue);
        if (!component.runningClearAllFilters && JSON.stringify(newValue) !== JSON.stringify(oldValue)) {
          component.runAjaxRequest();
        }
      });
      component.$watch('ages', function(newValue, oldValue){
        newValue = component.arrayFilter(newValue);
        oldValue = component.arrayFilter(oldValue);
        if (!component.runningClearAllFilters && JSON.stringify(newValue) !== JSON.stringify(oldValue)) {
          component.runAjaxRequest();
        }
      });
      component.$watch('days', function(newValue, oldValue){
        newValue = component.arrayFilter(newValue);
        oldValue = component.arrayFilter(oldValue);
        if (!component.runningClearAllFilters && JSON.stringify(newValue) !== JSON.stringify(oldValue)) {
          component.runAjaxRequest();
        }
      });
      component.$watch('sort', function(newValue, oldValue){
        newValue = component.arrayFilter(newValue);
        oldValue = component.arrayFilter(oldValue);
        if (!component.runningClearAllFilters && JSON.stringify(newValue) !== JSON.stringify(oldValue)) {
          component.runAjaxRequest();
        }
      });
    },
    methods: {
      arrayFilter: function(array) {
        if (typeof array != 'array') {
          return array;
        }
        return array.filter(function(word){ return word.length > 0 && word != 'undefined'; }).map(function (word) {
          if (isNaN(word)) {
            return word;
          }
          return +word;
        });
      },
      runAjaxRequest: function(reset_pager) {
        if (!reset_pager) reset_pager = true;
        var component = this;
        var url = drupalSettings.path.baseUrl + 'af/get-data';

        // If alternative search provided modify parameters to get some results for most important criteria.
        if (typeof this.alternativeCriteria !== 'undefined') {
          switch (this.alternativeCriteria) {
            case 'day':
              this.locations = [];
              this.categories = [];
              this.$refs.locations_filter.clear();
              this.$refs.categories_filter.clear();
              break;
            case 'program':
              this.days = [];
              this.locations = [];
              this.$refs.locations_filter.clear();
              this.$refs.days_filter.clear();
              break;
            case 'location':
              this.days = [];
              this.categories = [];
              this.$refs.categories_filter.clear();
              this.$refs.days_filter.clear();
              break;
          }
          this.alternativeCriteria = '';
        }

        var query = [],
        cleanLocations = this.locations.map(function(word){ return word; });
        if (cleanLocations.length > 0) {
          query.push('locations=' + encodeURIComponent(cleanLocations.join(',')));
        }
        if (this.keywords.length > 0 && this.keywords != 'undefined') {
          query.push('keywords=' + encodeURIComponent(this.keywords));
        }
        var cleanCategories = this.categories.map(function(word){ return word; });
        if (cleanCategories.length > 0) {
          query.push('categories=' + encodeURIComponent(cleanCategories.join(',')));
        }
        if (this.categoriesExcluded.length > 0) {
          query.push('exclude=' + encodeURIComponent(this.categoriesExcluded.join(',')));
        }
        var cleanAges = this.ages.filter(function(word){ return word; });
        if (cleanAges.length > 0) {
          query.push('ages=' + encodeURIComponent(cleanAges.join(',')));
        }
        var cleanDays = this.days.filter(function(word){ return word; });
        if (cleanDays.length > 0) {
          query.push('days=' + encodeURIComponent(cleanDays.join(',')));
        }
        if (typeof this.current_page != 'undefined' && this.current_page > 0) {
          // Undefined pager_info means it is Daxko.
          if (typeof pager_info == 'undefined' && typeof this.pages[this.current_page] != 'undefined') {
            query.push('next=' + encodeURIComponent(this.pages[this.current_page]));
          }

          // Reset pager if any of filters has changed in order to load 1st page.
          if (reset_pager === true) {
            this.current_page = 1;
          }
          query.push('page=' + encodeURIComponent(this.current_page));
        }
        if (typeof this.sort != 'undefined' && this.sort !== '') {
          query.push('sort=' + encodeURIComponent(this.sort));
        }

        if (query.length > 0) {
          url += '?' + query.join('&');
        }

        this.loading = true;

        $.getJSON(url, function(data) {
          component.table = data.table;
          component.facets = data.facets;
          component.count = data.count;
          component.pages[component.current_page + 1] = data.pager;
          component.pager_info = data.pager_info;
          component.no_results = data.count > 0 ? '0' : '1';
          component.sort = data.sort;

          router.push({ query: {
            locations: cleanLocations.join(','),
            categories: cleanCategories.join(','),
            exclude: component.categoriesExcluded.join(','),
            ages: cleanAges.join(','),
            days: cleanDays.join(','),
            keywords: component.keywords,
            page: component.page,
            no_results: component.no_results,
            sort: component.sort,
          }});
        }).done(function() {
          component.loading = false;
        });
      },
      searchAlternativeResults: function(type) {
        this.alternativeCriteria = type;
        this.runAjaxRequest();
      },
      populatePopupLocation: function(index) {
        this.table[index].location_info.address = this.convertAddressField(this.table[index].location_info.address);
        this.locationPopup = this.table[index].location_info;
      },
      convertAddressField: function(address) {
        address = address.split(',').map(function(item) {return item.trim()});
        let address_out = address[0] ? address[0] : '';
        address_out += '<br />';
        address_out += address[1] ? address[1] + ', ' : '';
        address_out += address[2] ? address[2] + ' ': '';
        address_out += address[3] ? address[3] : '';
        return address_out;
      },
      populatePopupMoreInfo: function(index) {
        var component = this;

        // This means we already have all data so no need to run extra ajax call.
        if (component.table[index].availability_status !== '') {
          component.moreInfoPopup.name = component.table[index].name;
          component.moreInfoPopup.description = component.table[index].description;

          component.moreInfoPopup.price = component.table[index].price;
          component.moreInfoPopup.ages = component.table[index].ages;
          component.moreInfoPopup.gender = component.table[index].gender;

          component.moreInfoPopup.dates = component.table[index].dates;
          component.moreInfoPopup.weeks = component.table[index].weeks;
          component.moreInfoPopup.schedule = component.table[index].schedule;

          component.moreInfoPopup.location_url = drupalSettings.path.baseUrl + 'node/' + component.table[index].location_info.nid;
          component.moreInfoPopup.location_name = component.table[index].location_info.title;

          component.moreInfoPopup.location_address = this.convertAddressField(component.table[index].location_info.address);
          component.moreInfoPopup.location_phone = component.table[index].location_info.phone;

          component.moreInfoPopup.availability_note = component.table[index].availability_note;
          component.moreInfoPopup.availability_status = component.table[index].availability_status;
          component.moreInfoPopup.link = component.table[index].link;
          component.moreInfoPopup.spots_available = component.table[index].spots_available;
          component.moreInfoPopup.learn_more = component.table[index].learn_more.replace('a href=', 'a target="_blank" href=');
          // Dynamically create more results depends on search criteria.
          if (component.table[index].more_results_type == 'keyword') {
            component.moreInfoPopup.more_results = '?' + 'keywords=' + encodeURIComponent(component.table[index].program_name) + '&ages=' + component.ages.join(',') + '&locations=' + component.table[index].location_id;
          }
          if (component.table[index].more_results_type == 'program') {
            component.moreInfoPopup.more_results = '?' + 'categories=' + component.table[index].program_id + '&ages=' + component.ages.join(',') + '&locations=' + component.table[index].location_id;
          }
          component.availabilityPopup.status = component.table[index].availability_status;
          component.availabilityPopup.note = component.table[index].availability_note;
          component.availabilityPopup.link = component.table[index].link;
          component.availabilityPopup.price = component.table[index].price;
          return;
        }

        var url = drupalSettings.path.baseUrl + 'af/more-info';

        // Pass all the query parameters to Details call so we could build the logging.
        var query = [];
        query.push('log=' + encodeURIComponent(this.table[index].log_id));
        query.push('details=' + encodeURIComponent(this.table[index].name));
        query.push('nid=' + encodeURIComponent(this.table[index].nid));

        query.push('program=' + encodeURIComponent(this.table[index].program_id));
        query.push('offering=' + encodeURIComponent(this.table[index].offering_id));
        query.push('location=' + encodeURIComponent(this.table[index].location_id));

        if (query.length > 0) {
          url += '?' + query.join('&');
        }

        component.moreInfoPopupLoading = true;
        $.getJSON(url, function(data) {
          component.moreInfoPopupLoading = false;

          component.table[index].price = data.price;
          component.table[index].availability_note = data.availability_note;
          component.table[index].availability_status = data.availability_status;
          component.table[index].spots_available = data.spots_available;
          component.table[index].ages = data.ages;
          component.table[index].gender = data.gender;
          component.table[index].description = data.description;
          component.table[index].link = data.link;
          component.table[index].program_name = data.program_name;

          component.moreInfoPopup.name = component.table[index].name;
          component.moreInfoPopup.description = component.table[index].description;

          component.moreInfoPopup.price = component.table[index].price;
          let age_output = component.table[index].ages;
          component.moreInfoPopup.ages = age_output;
          component.moreInfoPopup.gender = component.table[index].gender;

          component.moreInfoPopup.dates = component.table[index].dates;
          component.moreInfoPopup.schedule = component.table[index].schedule;

          component.moreInfoPopup.location_name = component.table[index].location_info.title;
          component.moreInfoPopup.location_address = component.table[index].location_info.address;
          component.moreInfoPopup.location_phone = component.table[index].location_info.phone;

          component.moreInfoPopup.availability_note = component.table[index].availability_note;
          component.moreInfoPopup.availability_status = component.table[index].availability_status;
          component.moreInfoPopup.spots_available = component.table[index].spots_available;
          component.moreInfoPopup.link = component.table[index].link;
          component.moreInfoPopup.learn_more = component.table[index].learn_more.replace('a href=', 'a target="_blank" href=');
          // Dynamically create more results depends on search criteria.
          if (component.table[index].more_results_type == 'keyword') {
            component.moreInfoPopup.more_results = '?' + 'keywords=' + encodeURIComponent(component.table[index].program_name) + '&ages=' + component.ages.join(',') + '&locations=' + component.table[index].location_id;
          }
          if (component.table[index].more_results_type == 'program') {
            component.moreInfoPopup.more_results = '?' + 'categories=' + component.table[index].program_id + '&ages=' + component.ages.join(',') + '&locations=' + component.table[index].location_id;
          }
          component.availabilityPopup.status = component.table[index].availability_status;
          component.availabilityPopup.note = component.table[index].availability_note;
          component.availabilityPopup.link = component.table[index].link;
          component.availabilityPopup.price = component.table[index].price;
        });
      },

      clearFilters: function() {
        this.runningClearAllFilters = true;
        this.locations = [];
        this.categories = [];
        this.ages = [];
        this.days = [];
        this.runningClearAllFilters = false;
        this.$refs.ages_filter.clear();
        this.$refs.locations_filter.clear();
        this.$refs.categories_filter.clear();
        this.$refs.days_filter.clear();
        this.runAjaxRequest();
      },
      loadPrevPage: function() {
        this.current_page--;
        this.table = [];
        this.runAjaxRequest(false);
        $('html, body').animate( { scrollTop: $('.schedule-dashboard__wrapper').offset().top - 200 }, 500 );
      },
      loadNextPage: function() {
        this.current_page++;
        this.table = [];
        this.runAjaxRequest(false);
        $('html, body').animate( { scrollTop: $('.schedule-dashboard__wrapper').offset().top - 200 }, 500 );
      },
      loadPageNumber: function(number) {
        this.current_page = number;
        this.table = [];
        this.runAjaxRequest(false);
        $('html, body').animate( { scrollTop: $('.schedule-dashboard__wrapper').offset().top - 200 }, 500 );
      },
      totalGroupCounter: function() {
        // Returns count of of applied filters.
        return this.ages.length + this.days.length + this.categories.length + this.locations.length;
      }
    },
    delimiters: ["${","}"]
  });

})(jQuery);

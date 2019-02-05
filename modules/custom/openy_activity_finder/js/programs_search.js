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
    props: ['title', 'id', 'options', 'default', 'type'],
    data: function() {
      return {
        checkboxes: [],
        checked: [],
        expanded: true,
        expanded_checkboxes: {},
        dependencies: {},
      }
    },
    created: function() {
      this.checkboxes = JSON.parse(this.options);
      this.checked = this.default.split(',');
      for (var i in this.checkboxes) {
        checkbox = this.checkboxes[i];
        if (typeof checkbox == 'object') {
          this.dependencies[checkbox.label] = [];
          for (var k in checkbox.value) {
            var item = checkbox.value[k];
            this.dependencies[checkbox.label].push(item.value);
          }
        }
      }
    },
    watch: {
      checked: function(values) {
        this.$emit('updated-values', values);
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
        return data.value;
      },
      getLabel: function(data) {
        return data.label;
      },
      collapseGroup: function(checkbox) {
        var label = this.getLabel(checkbox);
        return typeof this.expanded_checkboxes[label] == 'undefined' || this.expanded_checkboxes[label] == false;
      },
      groupStatus: function(value) {
        if (typeof this.dependencies[value] == 'undefined') {
          return false;
        }

        var foundChecked = false;
        var foundUnChecked = false;
        for (var i in this.dependencies[value]) {
          if (this.checked.indexOf(this.dependencies[value][i]) != -1) {
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
            Vue.set(this.checked, this.checked.length, this.dependencies[value][i]);
          }
          // If already checked but we need to uncheck.
          if (key != -1 && removeValue) {
            Vue.set(this.checked, key, '');
          }
        }
      }
    },
    template: '<div class="form-group-wrapper">\n' +
    '                <label v-on:click="expanded = !expanded">\n' +
    '                 {{ title }}\n' +
    '                  <i v-if="expanded" class="fa fa-minus minus" aria-hidden="true"></i>\n' +
    '                  <i v-if="!expanded" class="fa fa-plus plus" aria-hidden="true"></i>\n' +
    '                </label>\n' +
    '                <div v-bind:class="[type]">\n' +
    '                  <div v-for="checkbox in checkboxes" class="checkbox-wrapper" ' +
    '                     v-show="type != \'tabs\' || expanded || checked.indexOf(getOption(checkbox)) != -1"' +
    '                     v-bind:class="{\'col-xs-6 col-sm-4\': type == \'tabs\'}">' +
                         // No parent checkbox.
    '                    <input v-if="typeof getOption(checkbox) != \'object\'" v-show="expanded || checked.indexOf(getOption(checkbox)) != -1" type="checkbox" v-model="checked" :value="getOption(checkbox)" :id="\'checkbox-\' + id + \'-\' + getOption(checkbox)">\n' +
    '                    <label v-if="typeof getOption(checkbox) != \'object\'" v-show="expanded || checked.indexOf(getOption(checkbox)) != -1" :for="\'checkbox-\' + id + \'-\' + getOption(checkbox)">{{ getLabel(checkbox) }}</label>\n' +
                         // Locations with sub-locations/branches.
    '                    <div v-if="typeof getOption(checkbox) == \'object\'">' +
    '                       <a v-show="expanded" v-on:click.stop.prevent="selectDependent(getLabel(checkbox))" href="#" v-bind:class="{ ' +
    '                         \'checkbox-unchecked\': groupStatus(getLabel(checkbox)) == \'none\', ' +
    '                         \'checkbox-checked\': groupStatus(getLabel(checkbox)) == \'all\', ' +
    '                         \'checkbox-partial\': groupStatus(getLabel(checkbox)) == \'partial\', ' +
    '                         \'d-flex\': true' +
    '                       }">' +
    '                       <input v-if="typeof getOption(checkbox) == \'object\'" v-show="expanded || checked.indexOf(getOption(checkbox)) != -1" type="checkbox" v-model="checked">\n' +
    '                       <label v-if="typeof getOption(checkbox) == \'object\'" v-show="expanded || checked.indexOf(getOption(checkbox)) != -1" >{{ getLabel(checkbox) }}</label>\n' +
    '                       <a v-if="typeof getOption(checkbox) == \'object\' && expanded" href="#" class="checkbox-toggle-subset ml-auto">' +
    '                         <span v-show="collapseGroup(checkbox)" v-on:click.stop.prevent="Vue.set(expanded_checkboxes, getLabel(checkbox), true);" class="fa fa-angle-down" aria-hidden="true"></span>' +
    '                         <span v-if="typeof getOption(checkbox) == \'object\' && expanded" v-show="!collapseGroup(checkbox)" v-on:click.stop.prevent="expanded_checkboxes[getLabel(checkbox)] = false" class="fa fa-angle-up" aria-hidden="true"></span>' +
    '                       </a>' +
    '                    </div>' +
    '                    <div v-if="typeof getOption(checkbox) == \'object\'" v-for="checkbox2 in getOption(checkbox)" class="checkbox-wrapper">\n' +
    '                      <input v-if="checked.indexOf(getOption(checkbox2)) != -1 || (expanded && !collapseGroup(checkbox))" type="checkbox" v-model="checked" :value="getOption(checkbox2)" :id="\'checkbox-\' + id + \'-\' + getOption(checkbox2)">\n' +
    '                      <label v-if="checked.indexOf(getOption(checkbox2)) != -1 || (expanded && !collapseGroup(checkbox))" :for="\'checkbox-\' + id + \'-\' + getOption(checkbox2)">{{ getLabel(checkbox2) }}</label>\n' +
    '                    </div>\n' +
    '                  </div>\n' +
    '                </div>\n' +
    '              </div>'
  });

  // Does not yet support flat list of radios. Only two level as for Daxko location.
  Vue.component('sidebar-filter-single', {
    props: ['title', 'id', 'options', 'default', 'type'],
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
      this.checked = this.default;
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
      }
    },
    template: '<div class="form-group-wrapper">\n' +
    '                <label v-on:click="expanded = !expanded">\n' +
    '                 {{ title }}\n' +
    '                  <i v-if="expanded" class="fa fa-minus minus" aria-hidden="true"></i>\n' +
    '                  <i v-if="!expanded" class="fa fa-plus plus" aria-hidden="true"></i>\n' +
    '                </label>\n' +
    '                <div v-bind:class="[type]">\n' +
    '                  <div v-for="radio in radios" class="checkbox-wrapper" ' +
    '                     v-show="type != \'tabs\' || expanded || checked.indexOf(getOption(radio)) != -1">' +
    '                    <div v-if="typeof getOption(radio) == \'object\'">' +
    '                       <a v-if="typeof getOption(radio) == \'object\' && expanded" href="#" class="checkbox-toggle-subset ml-auto">' +
    '                         <label v-if="typeof getOption(radio) == \'object\'" v-on:click.stop.prevent="Vue.set(expanded_checkboxes, getLabel(radio), true);" v-show="collapseGroup(radio) && (expanded || checked.indexOf(getOption(radio)) != -1)" class="full-width">{{ getLabel(radio) }} <span class="fa fa-angle-down pull-right" aria-hidden="true"></span></label>\n' +
    '                         <label v-if="typeof getOption(radio) == \'object\'" v-on:click.stop.prevent="expanded_checkboxes[getLabel(radio)] = false" v-show="!collapseGroup(radio) && (expanded || checked.indexOf(getOption(radio)) != -1)" class="full-width">{{ getLabel(radio) }} <span v-if="typeof getOption(radio) == \'object\' && expanded" class="fa fa-angle-up pull-right" aria-hidden="true"></span></label>\n' +
    '                       </a>' +
    '                    </div>' +
    '                    <div v-if="typeof getOption(radio) == \'object\'" v-for="radio2 in getOption(radio)" class="checkbox-wrapper">\n' +
    '                      <input v-if="checked.indexOf(getOption(radio2)) != -1 || (expanded && !collapseGroup(radio))" type="radio" v-model="checked" :value="getOption(radio2)" :id="\'radio-\' + id + \'-\' + getOption(radio2)">\n' +
    '                      <label v-if="checked.indexOf(getOption(radio2)) != -1 || (expanded && !collapseGroup(radio))" :for="\'radio-\' + id + \'-\' + getOption(radio2)">{{ getLabel(radio2) }}</label>\n' +
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
      pages: { 0:'' },
      current_page: 0,
      keywords: '',
      locations: [],
      ages: [],
      days: [],
      categories: [],
      categoriesExcluded: [],
      categoriesLimit: [],
      moreInfoPopupLoading: false,
      runningClearAllFilters: false,
      afPageRef: '',
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
      },
      moreInfoPopup: {
        name: '',
        description: '',
        price: '',
        ages: '',
        gender: '',
        dates: '',
        times: '',
        days: '',
        location_name: '',
        location_address: '',
        location_phone: '',
        availability_status: '',
        availability_note: '',
        link: ''
      }
    },
    created: function() {
      var component = this;

      if (typeof this.$route.query.locations != 'undefined') {
        var locationsGet = decodeURIComponent(this.$route.query.locations);
        if (locationsGet) {
          this.locations = locationsGet.split(',');
        }
      }

      if (typeof this.$route.query.categories != 'undefined') {
        var categoriesGet = decodeURIComponent(this.$route.query.categories);
        if (categoriesGet) {
          this.categories = categoriesGet.split(',');
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

      component.afPageRef = 'OpenY' in window ? window.OpenY.field_prgf_af_page_ref[0]['url'] : '';

      // We add watchers dynamically otherwise initially there will be
      // up to three requests as we are changing values while initializing
      // from GET query parameters.
      component.$watch('locations', function(newValue, oldValue){
        newValue = component.arrayFilter(newValue);
        oldValue = component.arrayFilter(oldValue);
        if (!component.runningClearAllFilters && newValue.length != oldValue.length) {
          component.runAjaxRequest();
        }
      });
      component.$watch('categories', function(newValue, oldValue){
        newValue = component.arrayFilter(newValue);
        oldValue = component.arrayFilter(oldValue);
        if (!component.runningClearAllFilters && newValue != oldValue) {
          component.runAjaxRequest();
        }
      });
      component.$watch('ages', function(newValue, oldValue){
        newValue = component.arrayFilter(newValue);
        oldValue = component.arrayFilter(oldValue);
        if (!component.runningClearAllFilters && newValue.length != oldValue.length) {
          component.runAjaxRequest();
        }
      });
      component.$watch('days', function(newValue, oldValue){
        newValue = component.arrayFilter(newValue);
        oldValue = component.arrayFilter(oldValue);
        if (!component.runningClearAllFilters && newValue.length != oldValue.length) {
          component.runAjaxRequest();
        }
      });
    },
    methods: {
      arrayFilter: function(array) {
        if (typeof array != 'array') {
          return array;
        }
        return array.filter(function(word){ return word.length > 0 && word != 'undefined'; });
      },
      runAjaxRequest: function() {
        var component = this;

        var url = drupalSettings.path.baseUrl + 'af/get-data';

        var query = [];
        var cleanLocations = this.locations.filter(function(word){ return word; });
        if (cleanLocations.length > 0) {
          query.push('locations=' + encodeURIComponent(cleanLocations.join(',')));
        }
        if (this.keywords.length > 0 && this.keywords != 'undefined') {
          query.push('keywords=' + encodeURIComponent(this.keywords));
        }
        var cleanCategories = this.categories.filter(function(word){ return word; });
        if (cleanCategories.length > 0) {
          query.push('categories=' + encodeURIComponent(cleanCategories.join(',')));
        }
        var cleanAges = this.ages.filter(function(word){ return word; });
        if (cleanAges.length > 0) {
          query.push('ages=' + encodeURIComponent(cleanAges.join(',')));
        }
        var cleanDays = this.days.filter(function(word){ return word; });
        if (cleanDays.length > 0) {
          query.push('days=' + encodeURIComponent(cleanDays.join(',')));
        }
        if (typeof this.pages[this.current_page] != 'undefined') {
          query.push('next=' + encodeURIComponent(this.pages[this.current_page]));
          query.push('page=' + encodeURIComponent(this.current_page + 1));
        }

        if (query.length > 0) {
          url += '?' + query.join('&');
        }

        this.loading = true;

        $.getJSON(url, function(data) {
          component.table = data.table;
          component.count = data.count;
          component.pages[component.current_page + 1] = data.pager;
          component.loading = false;
        });

        router.push({ query: {
          locations: cleanLocations.join(','),
          categories: cleanCategories.join(','),
          ages: cleanAges.join(','),
          days: cleanDays.join(','),
          keywords: this.keywords,
          page: this.page
        }});
      },
      populatePopupLocation: function(index) {
        this.locationPopup = this.table[index].location_info;
      },
      populatePopupMoreInfo: function(index) {
        var component = this;

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
          component.moreInfoPopup = data;
          component.table[index].price = data.price;

          component.moreInfoPopup.dates = component.table[index].dates;
          component.moreInfoPopup.times = component.table[index].times;
          component.moreInfoPopup.days = component.table[index].days;

          component.moreInfoPopup.location_name = component.table[index].location_info.title;
          component.moreInfoPopup.location_address = component.table[index].location_info.address;
          component.moreInfoPopup.location_phone = component.table[index].location_info.phone;

          component.table[index].availability_status = data.availability_status;
          component.table[index].availability_note = data.availability_note;

          component.availabilityPopup.status = component.table[index].availability_status;
          component.availabilityPopup.note = component.table[index].availability_note;
          component.availabilityPopup.link = component.table[index].register_link;
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
        this.runAjaxRequest();
      },
      loadNextPage: function() {
        this.current_page++;
        this.table = [];
        this.runAjaxRequest();
      }
    },
    delimiters: ["${","}"]
  });

})(jQuery);

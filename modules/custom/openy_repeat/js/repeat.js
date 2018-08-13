(function ($) {
  if (!$('.schedule-dashboard__wrapper').length) {
    return;
  }

  var locationPage = window.OpenY.field_prgf_repeat_schedules_pref[0] || '';
  if (locationPage) {
    $('.clear-all').attr('href', locationPage.url).removeClass('hidden');
  }

  // +/- Toggle.
  $('.schedule-dashboard__sidebar .navbar-header a[data-toggle], .form-group-wrapper label[data-toggle]').on('click', function() {
    if (!$('.' + $(this).attr('for')).hasClass('collapsing')) {
      $(this)
        .toggleClass('closed active')
        .find('i')
        .toggleClass('fa-minus fa-plus');
    }
  });

  // PDF link show/hidden.
  var pdfLink = window.OpenY.field_prgf_repeat_schedules_pdf[0] || '';
  if (pdfLink) {
    $('.btn-schedule-pdf')
      .removeClass('hidden')
      .attr('href', pdfLink.url);
  }

  /* Check the settings of whether to display Instructor column or not */
  function displayInstructorOrNot() {
    var instructorDisplay = window.OpenY.field_prgf_repeat_schedule_instr[0].value;
    if (parseInt(instructorDisplay) != 1) {
      $('.instructor-column').remove();
    }
  }
  displayInstructorOrNot();

  // Set number of column classes.
  function calculateColumns() {
    if ($('.schedules-data__header').length > 0) {
      var colCount = $('.schedules-data__header > div').length;
      if ($('.schedules-data__row .register-btn').length === 0) {
        colCount = colCount - 1;
        $('.schedules-data__header > div').last().hide();
      }
      else {
        $('.schedules-data__header > div').last().show();
      }
      $('.schedules-data')
        .removeClass('schedules-data__cols-5')
        .removeClass('schedules-data__cols-6')
        .addClass('schedules-data__cols-' + colCount);
    }
  }

  var router = new VueRouter({
      mode: 'history',
      routes: []
  });

  // Retrieve the data via vue.js.
  new Vue({
    el: '#app',
    router,
    data: {
      table: {},
      date: '',
      locations: [],
      categories: [],
      categoriesExcluded: [],
      categoriesLimit: [],
      locationPopup: {
        address: '',
        email: '',
        phone: '',
        title: ''
      },
      classPopup: {
        title: '',
        description: ''
      }
    },
    created() {
      var component = this;
      // If there are any exclusions available from settings.
      var exclusionSettings = window.OpenY.field_prgf_repeat_schedule_excl || [];
      exclusionSettings.forEach(function(item){
        component.categoriesExcluded.push(item.title);
      });

      // If there is preselected category, we hide filters and column.
      var limitCategories = window.OpenY.field_prgf_repeat_schedule_categ || [];
      if (limitCategories) {
        // If we limit to one category. i.e. GroupExercises from GroupExPro
        if (limitCategories.length == 1) {
          component.categories.push(limitCategories[0].title);
          $('.form-group-category').parent().hide();
          $('.category-column').remove();
        }
        else {
          limitCategories.forEach(function(element){
            component.categoriesLimit.push(element.title);
          });

          $('.form-group-category .checkbox-wrapper input').each(function(){
            var value = $(this).attr('value');
            if (component.categoriesLimit.indexOf(value) === -1) {
              $(this).parent().hide();
            }
          });
        }
      }

      var dateGet = this.$route.query.date;
      if (dateGet) {
        this.date = dateGet;
      }
      else {
        this.date = moment().format('D MMM YYYY');
      }

      var locationsGet = this.$route.query.locations;
      if (locationsGet) {
        this.locations = locationsGet.split(',');
      }

      var categoriesGet = this.$route.query.categories;
      if (categoriesGet) {
        this.categories = categoriesGet.split(',');
      }

      this.runAjaxRequest();

      // We add watchers dynamically otherwise initially there will be
      // up to three requests as we are changing values while initializing
      // from GET query parameters.
      component.$watch('date', function(){ component.runAjaxRequest(); });
      component.$watch('locations', function(){ component.runAjaxRequest(); });
      component.$watch('categories', function(){ component.runAjaxRequest(); });
    },
    mounted() {
      /* It doesn't work if try to add datepicker in created. */
      var component = this;
      $('#datepicker input').datepicker({
        format: "MM d, DD",
        multidate: false,
        keyboardNavigation: false,
        forceParse: false,
        autoclose: true,
        todayHighlight: true
      }).on('changeDate', function() {
        if ($(this).val() != '') {
          component.date = moment($(this).datepicker('getDate')).format('D MMM YYYY');
        }
      });
    },
    computed: {
      dateFormatted: function(){
        return moment(this.date).format('MMMM D, dddd');
      }
    },
    methods: {
      runAjaxRequest: function() {
        var component = this;

        var url = drupalSettings.path.baseUrl + 'schedules/get-event-data';
        url += this.locations.length > 0 ? '/' + encodeURIComponent(this.locations.join(',')) : '/0';
        url += this.categories.length > 0 ? '/' + encodeURIComponent(this.categories.join(',')) : '/0';
        url += this.date ? '/' + encodeURIComponent(this.date) : '';

        var query = [];
        if (this.categoriesExcluded.length > 0) {
          query.push('excl=' + encodeURIComponent(this.categoriesExcluded.join(',')));
        }
        if (this.categoriesLimit.length > 1) {
          query.push('limit=' + encodeURIComponent(this.categoriesLimit.join(',')));
        }

        if (query.length > 0) {
          url += '?' + query.join('&');
        }

        $('.schedules-empty_results').addClass('hidden');
        $('.schedules-loading').removeClass('hidden');

        $.getJSON(url, function(data) {
          component.table = data;
          if (data.length === 0) {
            $('.schedules-empty_results').removeClass('hidden');
          }
          $('.schedules-loading').addClass('hidden');
        });

        router.push({ query: {
          date: this.date,
          locations: this.locations.join(','),
          categories: this.categories.join(',')
        }});
      },
      populatePopupL: function(index) {
        this.locationPopup = this.table[index].location_info;
      },
      populatePopupC: function(index) {
        this.classPopup = this.table[index].class_info;
      },
      backOneDay: function() {
        this.date = moment(this.date).add(-1, 'day').format('D MMM YYYY');
      },
      forwardOneDay: function() {
        this.date = moment(this.date).add(1, 'day').format('D MMM YYYY');
      },
      addToCalendarDate: function(dateTime) {
        var dateTimeArray = dateTime.split(' ');
        return moment(this.date).format('YYYY-MM-D') + ' ' + dateTimeArray[1];
      },
      categoryExcluded: function(category) {
        return this.categoriesExcluded.indexOf(category) !== -1;
      }
    },
    updated: function() {
      calculateColumns();
      if (typeof(addtocalendar) !== 'undefined') {
        addtocalendar.load();
      }
    },
    delimiters: ["${","}"]
  });

})(jQuery);

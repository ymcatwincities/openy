(function ($) {
  if (!$('.schedule-dashboard__wrapper').length) {
    return;
  }

  var locationPage = window.OpenY.field_prgf_repeat_schedules_pref[0] || '';
  if (locationPage) {
    $('.clear-all').attr('href', locationPage.url).removeClass('hidden');
  }

  var currentDate = moment().format('MMMM D, dddd'),
      // datepicker = $('#datepicker input'),
      eventLocation = '',
      eventCategory = '';

  var globalData = {
    date: currentDate,
    location: '',
    category: '',
    table: []
  };

  $('.form-group-location .box').on('click', function() {
    getValuesLocations();
  });

  $('.form-group-category .box').on('click', function() {
    getValuesCategories();
  });

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

  function runAjaxRequest(self, date, loc, cat) {
    // If there are any exclusions available from settings.
    var exclusionSettings = window.OpenY.field_prgf_repeat_schedule_excl || {};
    var excl = [];
    exclusionSettings.forEach(function(item){
      excl.push(item.title);
    });

    var url = drupalSettings.path.baseUrl + 'schedules/get-event-data';
    url += loc ? '/' + loc : '/0';
    url += cat ? '/' + cat : '/0';
    url += date ? '/' + date : '';
    url += excl ? '?excl=' + excl.join(',') : '';
    $('.schedules-empty_results').addClass('hidden');
    $('.schedules-loading').removeClass('hidden');
    $.getJSON(url, function(data) {
      self.globalData.table = data;
      if (data.length === 0) {
        $('.schedules-empty_results').removeClass('hidden');
      }
      $('.schedules-loading').addClass('hidden');
    });
    console.log('ajax ' + date + ' ' + loc + ' ' + cat);
  }

  function updateUrl(date, loc, cat) {
    router.push({ query: { date: date, locations: loc, categories: cat }});
  }

  function getValuesLocations() {
    var chkArray = [];

    $(".form-group-location .box").each(function() {
      if ($(this).is(':checked')) {
        chkArray.push(this.value);
      }
    });

    eventLocation = chkArray.join(',');
    globalData.location = eventLocation;
  }
  getValuesLocations();

  function getValuesCategories() {
    var chkArray = [];

    // If there is preselected category, we hide filters and column.
    var preSelectedCategory = window.OpenY.field_prgf_repeat_schedule_categ[0] || '';
    if (preSelectedCategory) {
      chkArray.push(preSelectedCategory.title);
      $('.form-group-category').parent().hide();
      $('.category-column').remove();
    }

    // If any categories should be excluded.
    var exclusionSettings = window.OpenY.field_prgf_repeat_schedule_excl || {};
    var excl = [];
    exclusionSettings.forEach(function(item){
      excl.push(item.title);
    });

    $(".form-group-category .box").each(function() {
      if (excl.indexOf($(this).attr('value')) !== -1) {
        $(this).parent().hide();
      }

      if ($(this).is(':checked')) {
        chkArray.push(this.value);
      }
    });

    eventCategory = chkArray.join(',');
    globalData.category = eventCategory;
  }
  getValuesCategories();

  /* Check the settings of whether to display Instructor column or not */
  function displayInstructorOrNot() {
    var instructorDisplay = window.OpenY.field_prgf_repeat_schedule_instr[0].value;
    if (parseInt(instructorDisplay) != 1) {
      $('.instructor-column').remove();
    }
  }
  displayInstructorOrNot();

  // Set number of column classes.
  if ($('.schedules-data__header').length > 0) {
    var colCount = $('.schedules-data__header > div').length;
    $('.schedules-data').addClass('schedules-data__cols-' + colCount);
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
      globalData: globalData,
      date: '',
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
    components: {
      //Results
    },
    created() {
      var dateGet = this.$route.query.date;
      if (dateGet) {
        this.date = dateGet;
      }
      else {
        this.date = moment().format('D MMM YYYY');
      }
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
    watch: {
      'date': function(newValue, oldValue) {
        runAjaxRequest(this, newValue, eventLocation, globalData.category);
        updateUrl(newValue, globalData.location, globalData.category);
      },
      'globalData.location': function(newValue, oldValue) {
        runAjaxRequest(this, currentDate, newValue, globalData.category);
        updateUrl(currentDate, newValue, globalData.category);
      },
      'globalData.category': function(newValue, oldValue) {
        runAjaxRequest(this, currentDate, globalData.location, newValue);
        updateUrl(currentDate, globalData.location, newValue);
      }
    },
    computed: {
      dateFormatted: function(){
        return moment(this.date).format('MMMM D, dddd');
      }
    },
    methods: {
      populatePopupL: function(index) {
        this.locationPopup = this.globalData.table[index].location_info;
      },
      populatePopupC: function(index) {
        this.classPopup = this.globalData.table[index].class_info;
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
      }
    },
    updated: function() {
      if (typeof(addtocalendar) !== 'undefined') {
        addtocalendar.load();
      }
    },
    delimiters: ["${","}"]
  });

})(jQuery);

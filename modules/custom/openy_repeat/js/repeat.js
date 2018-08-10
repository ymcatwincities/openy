(function ($) {
  if (!$('.schedule-dashboard__wrapper').length) {
    return;
  }

  $('.clear-all').attr('href', $('.field-prgf-repeat-schedules-pref a').attr('href'));

  var currentDate = moment().format('MMMM D, dddd'),
      datepicker = $('#datepicker input'),
      eventLocation = '',
      eventCategory = '';


  // Attach the datepicker.
  datepicker.datepicker({
    format: "MM d, DD",
    multidate: false,
    keyboardNavigation: false,
    forceParse: false,
    autoclose: true,
    todayHighlight: true
  });

  var globalData = {
    date: currentDate,
    location: '',
    category: '',
    table: []
  };

  datepicker.datepicker().on('changeDate', function() {
    if ($(this).val() != '') {
      currentDate = moment($(this).datepicker('getDate')).format('MMMM D, dddd');
      globalData.date = currentDate;
    }
  });

  $('.schedule-dashboard__arrow.right').on('click', function() {
    currentDate = moment(datepicker.datepicker('getDate')).add(1, 'day').format('MMMM D, dddd');
    globalData.date = currentDate;
  });

  $('.schedule-dashboard__arrow.left').on('click', function() {
    currentDate = moment(datepicker.datepicker('getDate')).add(-1, 'day').format('MMMM D, dddd');
    globalData.date = currentDate;
  });

  $('.form-group-location .box').on('click', function() {
    getValuesLocations();
  });

  $('.form-group-category .box').on('click', function() {
    getValuesCategories();
  });

  // +/- Toggle.
  $('.schedule-dashboard__sidebar .navbar-header a[data-toggle], .form-group-wrapper label[data-toggle]').on('click', function() {
    $(this)
      .toggleClass('closed active')
      .find('i')
      .toggleClass('fa-minus fa-plus');
  });

  // PDF link show/hidden.
  if ($('.field-prgf-repeat-schedules-pdf a').length > 0) {
    $('.btn-schedule-pdf')
      .removeClass('hidden')
      .attr('href', $('.field-prgf-repeat-schedules-pdf a').attr('href'));
  }

  function runAjaxRequest(self, date, loc, cat) {
    var url = drupalSettings.path.baseUrl + 'schedules/get-event-data';
    url += loc ? '/' + loc : '/0';
    url += cat ? '/' + cat : '/0';
    url += date ? '/' + date : '';
    $.getJSON(url, function(data) {
      self.globalData.table = data
    });
  }

  function updateUrl(date, loc, cat) {
    router.push({ query: { date: date, locations: loc, categories: cat }});
  }

  function changeDateTitle(text) {
    $('span.date').text(text);
    datepicker.datepicker('update', text);
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
    var preSelectedCategory = $('.field-prgf-repeat-schedule-categ a').html();
    if (preSelectedCategory) {
      chkArray.push(preSelectedCategory);
      $('.form-group-category').parent().hide();
      $('.category-column').remove();
    }

    // If any categories should be excluded.
    $('.field-prgf-repeat-schedule-excl').each(function(){
      // TODO: Remove category filters.
    });

    $(".form-group-category .box").each(function() {
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
    var instructorDisplay = $('.field-prgf-repeat-schedule-instr').html();
    if (instructorDisplay != 'Display') {
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
      locationPopup: {
        address: '',
        email: '',
        phone: '',
        title: ''
      }
    },
    components: {
      //Results
    },
    mounted() {
      runAjaxRequest(this, currentDate, eventLocation, eventCategory);
      changeDateTitle(currentDate);
    },
    watch: {
      'globalData.date': function(newValue, oldValue) {
        // this.$root.mounted();
        runAjaxRequest(this, newValue, eventLocation);
        changeDateTitle(newValue);
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
    methods: {
      populatePopup: function(index) {
        this.locationPopup = this.globalData.table[index].location_info;
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

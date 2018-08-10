(function ($) {
  if (!$('.schedule-dashboard__wrapper').length) {
    return;
  }

  var locationPage = window.OpenY.field_prgf_repeat_schedules_pref[0] || '';
  if (locationPage) {
    $('.clear-all').attr('href', locationPage.url).removeClass('hidden');
  }

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
    $.getJSON(url, function(data) {
      self.globalData.table = data
    });
  }

  function updateUrl(date, loc, cat) {
    router.push({ query: { date: date, locations: loc, categories: cat }});
  }

  function updateAtc() {
    var dp = moment(datepicker.datepicker('getDate')).format('YYYY-MM-D');
    $('.atc_date_start').each(function() {
      var d = $(this).text(),
          d1 = d.substring(0, 10),
          r = d.replace(d1, dp);
      $(this).html(r);
    });
    $('.atc_date_end').each(function() {
      var d = $(this).text(),
          d1 = d.substring(0, 10),
          r = d.replace(d1, dp);
      $(this).html(r);
    });
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
    mounted() {
      runAjaxRequest(this, currentDate, eventLocation, eventCategory);
      changeDateTitle(currentDate);
      updateAtc();
    },
    watch: {
      'globalData.date': function(newValue, oldValue) {
        // this.$root.mounted();
        runAjaxRequest(this, newValue, eventLocation, globalData.category);
        changeDateTitle(newValue);
        updateUrl(newValue, globalData.location, globalData.category);
        updateAtc();
      },
      'globalData.location': function(newValue, oldValue) {
        runAjaxRequest(this, currentDate, newValue, globalData.category);
        updateUrl(currentDate, newValue, globalData.category);
        updateAtc();
      },
      'globalData.category': function(newValue, oldValue) {
        runAjaxRequest(this, currentDate, globalData.location, newValue);
        updateUrl(currentDate, globalData.location, newValue);
        updateAtc();
      }
    },
    methods: {
      populatePopupL: function(index) {
        this.locationPopup = this.globalData.table[index].location_info;
      },
      populatePopupC: function(index) {console.log(this.globalData);
        this.classPopup = this.globalData.table[index].class_info;
      }
    },
    updated: function() {
      updateAtc();
      if (typeof(addtocalendar) !== 'undefined') {
        addtocalendar.load();
      }
    },
    delimiters: ["${","}"]
  });

})(jQuery);

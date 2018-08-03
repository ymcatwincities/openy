(function ($) {
  if (!$('.schedule-dashboard__wrapper').length) {
    return;
  }

  // Attach the datepicker.
  $("#datepicker").datepicker({
    autoclose: true
  });

  // Close the datepicker when clicking outside.
  $(document).click(function(e) {
    var ele = $(e.toElement);
    if (!ele.hasClass("hasDatepicker") && !ele.hasClass("ui-datepicker") && !ele.hasClass("ui-icon") && !$(ele).parent().parents(".ui-datepicker").length) {
      $(".hasDatepicker").datepicker("hide");
    }
  });

  $('.clear-all').attr('href', $('.field-prgf-repeat-schedules-pref a').attr('href'));

  var currentDate = moment().format('MMMM D, dddd'),
    eventLocation = '',
    eventCategory = '';

  var globalData = {
    date: currentDate,
    location: '',
    category: '',
    table: []
  };

  $("#datepicker input").val(currentDate);
  $('#datepicker input').datepicker().on('changeDate', function() {
    if ($(this).val() != '') {
      currentDate = moment($(this).datepicker('getDate')).format('MMMM D, dddd');
      globalData.date = currentDate;
    }
  });

  $('.schedule-dashboard__arrow.right').on('click', function() {
    currentDate = moment($('#datepicker input').datepicker('getDate')).add(1, 'day').format('MMMM D, dddd');
    globalData.date = currentDate;
    var d = $('#datepicker input').datepicker('getDate', '+1d');
    d.setDate(d.getDate()+1);
    $("#datepicker input").datepicker('setDate', d);
  });

  $('.schedule-dashboard__arrow.left').on('click', function() {
    currentDate = moment($('#datepicker input').datepicker('getDate')).add(-1, 'day').format('MMMM D, dddd');
    globalData.date = currentDate;
    var d = $('#datepicker input').datepicker('getDate', '-1d');
    d.setDate(d.getDate()+1);
    $("#datepicker input").datepicker('setDate', d);
  });

  $('.form-group-location .box').on('click', function() {
    getValuesLocations();
  });

  $('.form-group-category .box').on('click', function() {
    getValuesCategories();
  });

  // +/- toggle
  $('.schedule-dashboard__sidebar .navbar-header a[data-toggle], .form-group-wrapper label[data-toggle]').on('click', function() {
    $(this)
      .toggleClass('closed active')
      .find('i')
      .toggleClass('fa-minus fa-plus');
  });

  // PDF link show/hidden
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
      $('.category-column').hide();
      return;
    }

    $(".form-group-category .box").each(function() {
      if ($(this).is(':checked')) {
        chkArray.push(this.value);
      }
    });

    eventCategory = chkArray.join(',');
    globalData.category = eventCategory;
  }
  getValuesCategories();

  var router = new VueRouter({
      mode: 'history',
      routes: []
  });

  // Retrieve the data via vue.js.
  new Vue({
    el: '#app',
    router,
    data: {
      globalData: globalData
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
    updated: function() {
      if (typeof(addtocalendar) !== 'undefined') {
        addtocalendar.load();
      }
    },
    delimiters: ["${","}"]
  });

})(jQuery);

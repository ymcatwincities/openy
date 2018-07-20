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
    if (!ele.hasClass("hasDatepicker") && !ele.hasClass("ui-datepicker") && !ele.hasClass("ui-icon") && !$(ele).parent().parents(".ui-datepicker").length)
       $(".hasDatepicker").datepicker("hide");
  });

  var currentDate = moment().format('ll'),
    eventLocation = '';

  var globalData = {
    date: currentDate,
    location: '',
    table: []
  };

  $("#datepicker input").val(currentDate);
  $('#datepicker input').on('change', function() {
    if (this.value != '') {
      currentDate = moment(this.value).format('ll');
      globalData.date = currentDate;
    }
  });

  $('.schedule-dashboard__arrow.right').on('click', function() {
    currentDate = moment(currentDate).add(1, 'day').format('ll');
    globalData.date = currentDate;
    $("#datepicker input").val(currentDate);
  });

  $('.schedule-dashboard__arrow.left').on('click', function() {
    currentDate = moment(currentDate).add(-1, 'day').format('ll');
    globalData.date = currentDate;
    $("#datepicker input").val(currentDate);
  });

  $('.form-group-location .box').on('click', function() {
    getValueUsingClass();
  });

  // +/- toggle
  $('.form-group-wrapper label[data-toggle]').on('click', function() {
    $(this).find('i').toggleClass('fa-minus fa-plus');
  });

  function checkSelectedLocations() {
    // Remove single selected location from filtering.
    $('.selected-locations .remove').on('click', function () {
      var name = $(this).parent().find('.name').text();
      $('.checkbox input[value="' + name + '"]').each(function () {
        this.checked = false;
      });
      getValueUsingClass();
    });
  }

  function runAjaxRequest(self, date, loc) {
    var url = drupalSettings.path.baseUrl + 'schedules/get-event-data';
    url += loc ? '/' + loc : '/0';
    url += date ? '/' + date : '';
    $.getJSON(url, function(data) {
      self.globalData.table = data
    });
  }

  function changeDateTitle(text) {
    $('span.date').text(text);
  }

  function getValueUsingClass() {
    var chkArray = [];

    $(".form-group-location .box").each(function() {
      if ($(this).is(':checked')) {
        chkArray.push(this.value);
      }
    });

    eventLocation = chkArray.join(',');
    globalData.location = eventLocation;
  }
  getValueUsingClass();

  // Retrieve the data via vue.js.
  new Vue({
    el: '#app',
    data: {
      globalData: globalData
    },
    components: {
      //Results
    },
    mounted() {
      runAjaxRequest(this, currentDate, eventLocation);
      changeDateTitle(currentDate);
      checkSelectedLocations();
    },
    watch: {
      'globalData.date': function(newValue, oldValue) {
        // this.$root.mounted();
        runAjaxRequest(this, newValue, eventLocation);
        changeDateTitle(newValue);
      },
      'globalData.location': function(newValue, oldValue) {
        runAjaxRequest(this, currentDate, newValue);
        checkSelectedLocations();
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

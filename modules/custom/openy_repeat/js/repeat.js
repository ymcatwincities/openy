(function ($) {
  if (!$('.schedule-dashboard__wrapper').length) {
    return;
  }

  $("#datepicker").datepicker();

  // select the target node
  var headerTarget = document.querySelector('#page-head .top-navs');
  var footerTarget = document.querySelector('footer.footer');
  var bread = document.querySelector('.breadcrumbs');
  var dest = document.querySelector('.schedule-dashboard__sidebar');

  function isScrolledIntoView(el) {
    var rect = el.getBoundingClientRect();
    return (rect.top >= 0) && (rect.top <= window.innerHeight);
  }

  function checkBreadcrums() {
    var breadRect = bread.getBoundingClientRect();
    var navRect = headerTarget.getBoundingClientRect();
    if (navRect.bottom > breadRect.bottom) {
      dest.style.top = navRect.bottom + 'px';
    }
    else {
      dest.style.top = breadRect.bottom + 'px';
    }
  }

  // Fix the sidebars position.
  // window.onscroll = function() {
  //   if (isScrolledIntoView(footerTarget)) {
  //     var rect = footerTarget.getBoundingClientRect();
  //     dest.style.bottom = (window.innerHeight - rect.top) + "px";
  //   }
  //   else {
  //     dest.style.bottom = 0;
  //   }
  //   checkBreadcrums();
  // };

  // Create an observer instance.
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      // dest.style.top = headerTarget.getBoundingClientRect().bottom + "px";
      // checkBreadcrums();
    });
  });

  // configuration of the observer:
  var config = { attributes: true };

  // pass in the target node, as well as the observer options
  observer.observe(headerTarget, config);

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

  // Reset all the selected filters and set date to today.
  $('.clear-all').on('click', function(e) {
    e.preventDefault();

    $(".checkbox input").each(function() {
      this.checked = false;
    });

    getValueUsingClass();
    currentDate = moment().format('ll');
    $("#datepicker input").val(currentDate);
    globalData.date = currentDate;
  });

  $('.form-group-location .box').on('click', function() {
    getValueUsingClass();
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

(function ($) {
  if (!$('.repeat-schedule').length) {
    return;
  }

  $("#datepicker").datepicker();

  // select the target node
  var head_target = document.querySelector('#page-head .top-navs');
  var foter_target = document.querySelector('footer.footer');
  var bread = document.querySelector('.breadcrumbs');
  var dest = document.querySelector('.js-sidebar');

  function isScrolledIntoView(el) {
      var rect = el.getBoundingClientRect();
      return (rect.top >= 0) && (rect.top <= window.innerHeight);
  }

  function checkBreadcrums() {
    var bread_rect = bread.getBoundingClientRect();
    var nav_rect = head_target.getBoundingClientRect();
    if (nav_rect.bottom > bread_rect.bottom) {
      dest.style.top = nav_rect.bottom + 'px';
    }
    else {
      dest.style.top = bread_rect.bottom + 'px';
    }
  }

  window.onscroll = function() {
    if (isScrolledIntoView(foter_target)) {
      var rect = foter_target.getBoundingClientRect();
      dest.style.bottom = (window.innerHeight - rect.top) + "px";
    }
    else {
      dest.style.bottom = 0;
    }
    checkBreadcrums();
  };

  // Create an observer instance.
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      dest.style.top = head_target.getBoundingClientRect().bottom + "px";
      checkBreadcrums();
    });    
  });

  // configuration of the observer:
  var config = { attributes: true };

  // pass in the target node, as well as the observer options
  observer.observe(head_target, config);

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

  $('span.right').on('click', function() {
    currentDate = moment(currentDate).add(1, 'day').format('ll');
    globalData.date = currentDate;
    $("#datepicker input").val(currentDate);
  });
  $('span.left').on('click', function() {
    currentDate = moment(currentDate).add(-1, 'day').format('ll');
    globalData.date = currentDate;
    $("#datepicker input").val(currentDate);
  });
  // Reset all the selected filters and set date to today.
  $('.clear-all').on('click', function() {
    $(".checkbox input").each(function() {
      this.checked = false;
    });
    getValueUsingClass();
    currentDate = moment().format('ll');
    $("#datepicker input").val(currentDate);
    globalData.date = currentDate;
  });
  $('.location .box').on('click', function() {
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
    var url = '/schedules/get-event-data';
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
    var chkArray = [],
        selected_locations = '';

    $(".location .box").each(function() {
      if ($(this).is(':checked')) {
        chkArray.push(this.value);
        selected_locations += '<div><span class="name">' + $(this).val() + '</span><span class="remove">x</span></div>';
      }
    });
    $('.selected-locations').html(selected_locations);

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

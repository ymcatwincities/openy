(function ($) {
  if (!$('.repeat-schedule').length) {
    return;
  }

  var text = '18.05.89';
  changeDateTitle(text);

  function changeDateTitle(text) {
    $('span.date').text(text);
  }
  
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
  }

  // create an observer instance
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

  var curentDate = moment().format('ll'),
    eventLocation = '';


  var globalData = {
    date: curentDate,
    location: '',
    table: []
  };



  $('#datepicker input').on('change', function() {
    if (this.value != '') {
      curentDate = moment(this.value).format('ll');
      globalData.date = curentDate;
    }
  });


  $('span.right').on('click', function() {
    curentDate = moment(curentDate).add(1, 'day').format('ll');
    globalData.date = curentDate;
  });

  $('span.left').on('click', function() {
    curentDate = moment(curentDate).add(-1, 'day').format('ll');
    globalData.date = curentDate;
  });

  $('.location .box').on('click', function() {
    getValueUsingClass();
  });


  function runAjaxRequest(self, date, loc) {
    var url = '/programs/get-event-data';
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

    $(".location .box").each(function() {
      if ($(this).is(':checked')) {
        chkArray.push("'" + this.value + "'");
      }
    });

    eventLocation = chkArray.join(',');
    globalData.location = eventLocation;
  }

  new Vue({
    el: '#app',
    data: {
      globalData: globalData
    },
    components: {
      //Results
    },
    mounted() {
      runAjaxRequest(this, curentDate, eventLocation);
      changeDateTitle(curentDate);
    },
    watch: {
      'globalData.date': function(newValue, oldValue) {
        // this.$root.mounted();
        runAjaxRequest(this, newValue, eventLocation);
        changeDateTitle(newValue);
      },
      'globalData.location': function(newValue, oldValue) {
        runAjaxRequest(this, curentDate, newValue);
      }
    },
    delimiters: ["${","}"]
  });

})(jQuery);

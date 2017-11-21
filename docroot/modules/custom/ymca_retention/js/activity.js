(function ($) {

  Drupal.behaviors.ymca_retention_activity = {};
  Drupal.behaviors.ymca_retention_activity.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-activity-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-activity-processed');

    Drupal.ymca_retention.angular_app.controller('ActivityController', function (storage) {
      var self = this;
      // Shared information.
      self.storage = storage;

      // Show select list.
      self.dates_type_select = false;
      // Show slick carousel.
      self.dates_type_carousel = true;
      self.getTodayIndex = function() {
        if (!self.storage.dates || self.storage.dates.length == 0) {
          return -1;
        }
        for (var index in self.storage.dates) {
          if (self.storage.dates[index].today) {
            return index;
          }
        }
        return -1;
      };
      self.current_date_index = self.getTodayIndex();

      if (typeof self.storage.dates === 'undefined') {
        return;
      }
      self.storage.dates.forEach(function (item, i, arr) {
        if (item.past) {
          self.current_date_index = i;
        }
      });

      // @todo Reimplement the current logic since in case the current date is
      // not in the range of date_reporting_open and date_reporting_close dates
      // it eventually leads to exceptions since self.date_selected gets
      // undefined.
      self.date_selected = self.storage.dates[self.current_date_index] || { index: 0 };
      self.dateClass = function (index) {
        var classes = [];
        if (self.storage.dates[index].past && self.date_selected.index != index) {
          classes.push('campaign-dates--date-past');
        }
        if (self.storage.dates[index].today) {
          classes.push('campaign-dates--date-today');
        }
        if (self.date_selected.index === index) {
          classes.push('campaign-dates--date-current');
        }
        if (self.storage.dates[index].future) {
          classes.push('campaign-dates--date-future');
        }

        if (!self.activitiesCount(index)) {
          classes.push('campaign-dates--date-no-activity');
        }

        if (self.isVisited(index)) {
          classes.push('campaign-dates--date-visited');
        }

        return classes.join(' ');
      };
      self.activitiesCount = function (index) {
        if (!self.storage.member_activities_counts || self.storage.member_activities_counts.length == 0) {
          return 0;
        }

        var count = 0;
        for (var activity in self.storage.member_activities_counts[self.storage.dates[index].timestamp]) {
          if (self.storage.member_activities_counts[self.storage.dates[index].timestamp][activity]) {
            count++;
          }
        }
        return count;
      };

      self.activity_group_index = 0;
      self.activityGroupClass = function (index, activity_group) {
        var classes = [];
        classes.push('activity-tab__type-' + activity_group.retention_activity_id);

        if (self.activity_group_index === index) {
          classes.push('active');
        }

        return classes.join(' ');
      };
      self.activityGroupSet = function (index) {
        self.activity_group_index = index;
      };
      self.activityGroupShow = function (index) {
        return self.activity_group_index === index;
      };

      // Track the last clicked activity id.
      self.last_activity_id = -1;
      self.activityItemChange = function (id) {
        self.last_activity_id = id;
        var timestamp = self.date_selected.timestamp;
        var data = {
          'timestamp': timestamp,
          'id': id,
          'value': self.storage.member_activities[timestamp][id]
        };

        self.storage.setMemberActivities(data);
      };
      self.activityItemClass = function (id) {
        var classes = [];
        if (id === self.last_activity_id) {
          classes.push('activity--click-last');
        }

        return classes.join(' ');
      };

      // Set date via carousel.
      self.setDate = function (index) {
        if (self.storage.dates[index].past) {
          self.date_selected = self.storage.dates[index];
        }
      };

      // Check visits for a date.
      self.isVisited = function (index) {
        if (!self.storage.member_checkins || self.storage.member_checkins.length == 0) {
          return false;
        }
        if (self.storage.member_checkins[self.storage.dates[index].timestamp]) {
          return true;
        }
        return false;
      };

      // Slick config.
      self.slickConfig = {
        speed: 300,
        infinite: false,
        swipeToSlide: true,
        touchMove: true,
        variableWidth: true,
        initialSlide: (function () {
          var current_index = Math.max(0, self.date_selected.index - 3);
          var ww = jQuery(window).width();
          if (ww >= 1024) {
            current_index = Math.min(current_index, self.storage.dates.length - 11);
          }
          else if (ww >= 900) {
            current_index = Math.min(current_index, self.storage.dates.length - 8);
          }
          else if (ww >= 750) {
            current_index = Math.min(current_index, self.storage.dates.length - 6);
          }
          else if (ww >= 600) {
            current_index = Math.min(current_index, self.storage.dates.length - 5);
          }
          else {
            current_index = Math.min(current_index, self.storage.dates.length - 4);
          }
          return current_index;
        })(),
        slidesToScroll: 11,
        slidesToShow: 11,
        responsive: [
          {
            breakpoint: 1024,
            settings: {
              slidesToShow: 8,
              slidesToScroll: 8
            }
          },
          {
            breakpoint: 900,
            settings: {
              slidesToShow: 6,
              slidesToScroll: 6
            }
          },
          {
            breakpoint: 750,
            settings: {
              slidesToShow: 5,
              slidesToScroll: 5
            }
          },
          {
            breakpoint: 600,
            settings: {
              slidesToShow: 4,
              slidesToScroll: 4
            }
          },
          {
            breakpoint: 400,
            settings: {
              slidesToShow: 3,
              slidesToScroll: 3
            }
          }
        ]
      };
    });
  };

})(jQuery);

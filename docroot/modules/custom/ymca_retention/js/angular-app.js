(function($) {

  Drupal.behaviors.ymca_retention_angular_app = {};
  Drupal.behaviors.ymca_retention_angular_app.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-angular-app-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-angular-app-processed');

    // Create base tag for Angular UI router.
    $('head').append('<base href="' + settings.path.baseUrl + '">');

    Drupal.ymca_retention = Drupal.ymca_retention || {};
    Drupal.ymca_retention.angular_app = Drupal.ymca_retention.angular_app || angular.module('Retention', [
        'ngCookies', 'ui.router', 'ajoslin.promise-tracker', 'slickCarousel'
      ]);

    // Default parameters of Angular UI Router state.
    var defaultStateParams = {
      tab: 'tab_1',
      active_tab: 'tab_1',
      update_mobile: true
    };
    Drupal.ymca_retention.angular_app.config(function($locationProvider, $stateProvider) {
      $locationProvider.html5Mode({
        enabled: true,
        rewriteLinks: false
      });

      var state = {
        name: 'main',
        url: '/challenge?tab',
        params: defaultStateParams,
        onEnter: function(storage, $stateParams) {
          // Early return if active_tab is the same as requested tab.
          if ($stateParams.active_tab === $stateParams.tab) {
            return;
          }

          // Flag to control if active tab was changed.
          var active_tab_changed = false;

          // Handle not protected tabs.
          if ($stateParams.tab === 'tab_1' || $stateParams.tab === 'tab_4' || $stateParams.tab === 'tab_5') {
            $stateParams.active_tab = $stateParams.tab;
            active_tab_changed = true;
          }

          // Handle protected tabs.
          if (storage.member_loaded && storage.campaign_loaded) {
            if (storage.campaign.started) {
              if (storage.member) {
                $stateParams.active_tab = $stateParams.tab;
                active_tab_changed = true;
              }
              else {
                // Activate login popup.
                $('a[data-tab-id="' + $stateParams.tab + '"][data-type="login"]').click();
              }
            }
            else {
              // Activate days left popup.
              $('a[data-tab-id="' + $stateParams.tab + '"][data-type="tabs-lock"]').click();
            }
          }

          // Update mobile accordion if it is visible and active tab changed.
          if ($('.compain-accordion').is(':visible') && $stateParams.update_mobile && active_tab_changed) {
            $stateParams.update_mobile = false;
            // Resetting accordion.
            $('.compain-accordion .in').removeClass('in').addClass('collapse');
            $('.compain-accordion .panel-heading a').addClass('collapsed');

            // Expanding selected accordion item.
            $('.compain-accordion a[href="#' + $stateParams.tab + '-collapse"]').removeClass('collapsed');
            $('.compain-accordion #' + $stateParams.tab + '-collapse').addClass('in').css('height', 'auto');

            $('body').animate({
              scrollTop: $('a[href="#' + $stateParams.tab + '-collapse"]').offset().top
            });
          }
        },
        resolve: {
          stateParams: function(storage, $stateParams) {
            storage.stateParams = $stateParams;
          }
        }
      };

      $stateProvider.state(state);
    });

    Drupal.ymca_retention.angular_app.controller('RetentionController', function ($sce, $anchorScroll, $state, storage) {
      var self = this;
      // Shared information.
      self.storage = storage;

      self.tabSelectorClass = function (tab) {
        var classes = [];
        if (!angular.isUndefined(self.storage.stateParams) && self.storage.stateParams.active_tab == tab) {
          classes.push('active');
        }
        return classes.join(' ');
      };
      self.tabClass = function (tab) {
        var classes = [];
        if (!angular.isUndefined(self.storage.stateParams) && self.storage.stateParams.active_tab == tab) {
          classes.push('active');
          classes.push('in');
        }
        return classes.join(' ');
      };
      self.tabSelectorClick = function (tab, update_mobile) {
        $state.go('main', {tab: tab, update_mobile: update_mobile}, {reload: true});
      };

      // Message for days left popup.
      self.daysLeftMessage = function () {
        return $sce.trustAsHtml(Drupal.formatPlural(
          self.storage.campaign.days_left,
          '1 day left',
          '@count days left'
        ));
      };

      self.scrollTop = function () {
        $anchorScroll('top');
      };

      self.logOut = function () {
        // Activate first tab.
        $state.go('main', {tab: 'tab_1'});
        // Remove member cookie.
        self.storage.memberCookieRemove();
      };

      self.instantWinClass = function () {
        var classes = [];
        if (!self.storage.instantWinCount) {
          classes.push('empty');
        }
        return classes.join(' ');
      };
    });

    // Service to communicate with backend.
    Drupal.ymca_retention.angular_app.factory('courier', function($http, $q, $cookies, $httpParamSerializerJQLike) {
      function getCampaign() {
        var deferred = $q.defer();
        $http.get(settings.ymca_retention.resources.campaign).then(function(response) {
          deferred.resolve(response.data);
        });

        return deferred.promise;
      }

      function getSpring2017Campaign() {
        var deferred = $q.defer();
        $http.get(settings.ymca_retention.resources.spring2017campaign).then(function(response) {
          deferred.resolve(response.data);
        });

        return deferred.promise;
      }

      function getMember(id) {
        var deferred = $q.defer();
        if (typeof id === 'undefined') {
          deferred.resolve(null);
        }
        else {
          $http.get(settings.ymca_retention.resources.member).then(function(response) {
            if ($.isEmptyObject(response.data)) {
              // We've got empty result - remove the member cookie.
              $cookies.remove('Drupal.visitor.ymca_retention_member', { path: '/' });
              deferred.resolve(null);
              return;
            }

            deferred.resolve(response.data);
          });
        }

        return deferred.promise;
      }

      /**
       * Get information about member check-in history.
       * @param id Member Id.
       * @returns {*}
       */
      function getMemberCheckIns(id) {
        var deferred = $q.defer();
        if (typeof id === 'undefined') {
          deferred.resolve(null);
        }
        else {
          $http.get(settings.ymca_retention.resources.member_checkins).then(function (response) {
            if ($.isEmptyObject(response.data)) {
              deferred.resolve(null);
              return;
            }

            deferred.resolve(response.data);
          });
        }

        return deferred.promise;
      }

      function getMemberBonuses(id) {
        var deferred = $q.defer();
        if (typeof id === 'undefined') {
          deferred.resolve(null);
        }
        else {
          $http.get(settings.ymca_retention.resources.member_bonuses).then(function (response) {
            if ($.isEmptyObject(response.data)) {
              deferred.resolve(null);
              return;
            }

            deferred.resolve(response.data);
          });
        }

        return deferred.promise;
      }

      function getMemberActivities(id) {
        var deferred = $q.defer();
        if (typeof id === 'undefined') {
          deferred.resolve(null);
        }
        else {
          $http.get(settings.ymca_retention.resources.member_activities).then(function(response) {
            if ($.isEmptyObject(response.data)) {
              deferred.resolve(null);
              return;
            }

            deferred.resolve(response.data);
          });
        }

        return deferred.promise;
      }

      function setMemberActivities(data) {
        var id = $cookies.get('Drupal.visitor.ymca_retention_member'),
          deferred = $q.defer();
        if (typeof id === 'undefined') {
          deferred.resolve(null);
        }
        else {
          $http({
            method: 'POST',
            url: settings.ymca_retention.resources.member_activities,
            data: $httpParamSerializerJQLike(data),
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            }
          }).then(function(response) {
            if ($.isEmptyObject(response.data)) {
              deferred.resolve(null);
              return;
            }

            deferred.resolve(response.data);
          });
        }

        return deferred.promise;
      }

      function setMemberBonus(data) {
        var id = $cookies.get('Drupal.visitor.ymca_retention_member'),
          deferred = $q.defer();
        if (typeof id === 'undefined') {
          deferred.resolve(null);
        }
        else {
          $http({
            method: 'POST',
            url: settings.ymca_retention.resources.member_add_bonus,
            data: $httpParamSerializerJQLike(data),
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            }
          }).then(function(response) {
            if ($.isEmptyObject(response.data)) {
              deferred.resolve(null);
              return;
            }

            deferred.resolve(response.data);
          });
        }

        return deferred.promise;
      }

      function getMemberChances(id) {
        var deferred = $q.defer();
        if (typeof id === 'undefined') {
          deferred.resolve(null);
        }
        else {
          $http.get(settings.ymca_retention.resources.member_chances).then(function(response) {
            if ($.isEmptyObject(response.data)) {
              deferred.resolve(null);
              return;
            }

            deferred.resolve(response.data);
          });
        }

        return deferred.promise;
      }

      function getMemberPrize() {
        var id = $cookies.get('Drupal.visitor.ymca_retention_member'),
          deferred = $q.defer();
        if (typeof id === 'undefined') {
          deferred.resolve(null);
        }
        else {
          $http({
            method: 'POST',
            url: settings.ymca_retention.resources.member_chances,
            // data: $httpParamSerializerJQLike(data),
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            }
          }).then(function(response) {
            if ($.isEmptyObject(response.data)) {
              deferred.resolve(null);
              return;
            }

            deferred.resolve(response.data);
          });
        }

        return deferred.promise;
      }

      function getRecentWinners() {
        var deferred = $q.defer();
        $http.get(settings.ymca_retention.resources.recent_winners).then(function(response) {
          if ($.isEmptyObject(response.data)) {
            deferred.resolve(null);
            return;
          }

          deferred.resolve(response.data);
        });

        return deferred.promise;
      }

      function getTodaysInsight() {
        var deferred = $q.defer();
        $http.get(settings.ymca_retention.resources.todays_insight).then(function(response) {
          if ($.isEmptyObject(response.data)) {
            deferred.resolve(null);
            return;
          }

          deferred.resolve(response.data);
        });

        return deferred.promise;
      }

      return {
        getCampaign: getCampaign,
        // getSpring2017Campaign: getSpring2017Campaign,
        getMember: getMember,
        getMemberCheckIns: getMemberCheckIns,
        // getMemberBonuses: getMemberBonuses,
        getMemberActivities: getMemberActivities,
        setMemberActivities: setMemberActivities
        // setMemberBonus: setMemberBonus,
        // getMemberChances: getMemberChances,
        // getMemberPrize: getMemberPrize,
        // getRecentWinners: getRecentWinners,
        // getTodaysInsight: getTodaysInsight
      };
    });

    // Service to hold information shared between controllers.
    Drupal.ymca_retention.angular_app.service('storage', function($rootScope, $interval, $timeout, $cookies, $filter, promiseTracker, $state, courier) {
      var self = this;
      // Initiate the promise tracker to track submissions.
      self.progress = promiseTracker();

      // TODO: refactor this so that every controller sets his initial values itself.
      self.setInitialValues = function() {
        // Set activity tracker settings if available.
        if (settings.ymca_retention.activity !== undefined) {
          self.dates = settings.ymca_retention.activity.dates;
          self.activity_groups = settings.ymca_retention.activity.activity_groups;
        }
        self.campaign = {started: false, days_left: 50, current_day_timestamp: +new Date()};
        self.campaign_loaded = false;
        self.spring2017campaign = {};
        self.loss_messages = settings.ymca_retention.loss_messages;
        self.member = [];
        self.member_loaded = false;
        self.member_activities = [];
        self.member_activities_counts = [];
        // self.member_chances = null;
        // self.instantWinCount = 0;
        self.member_checkins = [];
        self.member_bonuses = [];
        self.recent_winners = [];
        self.last_played_chance = null;
        self.todays_insight = [];
        self.todays_insight_timestamp = null;
        self.todays_insight_timer = 30;
        self.timestamp_ids = [];
        // Game state.
        self.state = 'game';
      };
      self.setInitialValues();

      // Watch campaign_loaded and member_loaded to switch state
      // and either activate requested tabs or show popups.
      $rootScope.$watch(function () {
        if (self.campaign_loaded && self.member_loaded) {
          if (self.member) {
            return 2;
          }

          return 1;
        }

        return 0;
      }, function (newVal, oldVal) {
        if (newVal) {
          $state.go('main', {update_mobile: true}, {reload: true});
        }
      });

      // Force to check cookie value.
      $interval(function() {
        $cookies.get('Drupal.visitor.ymca_retention_member');
      }, 500);
      // Watch cookie value and update data on change.
      $rootScope.$watch(function() {
        return $cookies.get('Drupal.visitor.ymca_retention_member');
      }, function(newVal, oldVal) {
        self.getMember(newVal);
        self.getMemberCheckIns(newVal);
        self.getMemberActivities(newVal);
        // self.getMemberBonuses(newVal);
        // self.todaysInsightToDefault();
        self.state = 'game';
      });

      // Watch member activities and update counts.
      $rootScope.$watch(function() {
        return self.member_activities;
      }, function(newVal, oldVal) {
        self.memberActivitiesCounts();
      });

      // Watch member chances to update available instant win count.
      $rootScope.$watch(function() {
        return self.member_chances;
      }, function(newVal, oldVal) {
        if (!newVal) {
          self.instantWinCount = 0;
        }
        else {
          self.instantWinCount = $filter('filter')(newVal, {'played': '0'}, true).length;
        }
      });

      self.calculateLastPlayedChance = function(data) {
        var timestamp = 0;
        var last_played_chance;

        $.each(data, function(index, value) {
          if (value.played != '0' && value.played >= timestamp) {
            last_played_chance = value;
          }
        });

        return last_played_chance;
      };

      self.todaysInsightToDefault = function () {
        if (angular.isUndefined(self.campaign.dates)) {
          return;
        }
        var currentDay = self.campaign.dates[self.campaign.days_left - 1];
        self.todays_insight_timestamp = currentDay.timestamp;
        self.todays_insight_timer = 30;
        var i = 0;
        self.campaign.dates.forEach(function (entry) {
          self.timestamp_ids[entry.timestamp] = i++;
        });
      };

      self.getCampaign = function() {
        courier.getCampaign().then(function(data) {
          self.campaign = data;
          self.campaign_loaded = true;
          self.todaysInsightToDefault();
        });
      };
      self.getCampaign();

      self.getSpring2017Campaign = function() {
        courier.getSpring2017Campaign().then(function(data) {
          self.spring2017campaign = data;
        });
      };
      // self.getSpring2017Campaign();

      self.getMember = function(id) {
        courier.getMember(id).then(function(data) {
          self.member = data;
          self.member_loaded = true;
        });
      };

      self.getMemberChances = function() {
        var id = $cookies.get('Drupal.visitor.ymca_retention_member');
        return self.getMemberChancesById(id);
      };
      self.getMemberChancesById = function(id) {
        return courier.getMemberChances(id).then(function(data) {
          self.last_played_chance = self.calculateLastPlayedChance(data);
          self.member_chances = data;
        });
      };

      self.getMemberBonuses = function(id) {
        courier.getMemberBonuses(id).then(function(data) {
          self.member_bonuses = data;
        });
      };

      self.getMemberCheckIns = function(id) {
        courier.getMemberCheckIns(id).then(function(data) {
          self.member_checkins = data;
        });
      };
      self.setMemberBonus = function(data) {
        var $promise = courier.setMemberBonus(data).then(function(data) {
          // Update member bonuses.
          self.member_bonuses = data;
        });

        // Track the request and show its progress to the user.
        self.progress.addPromise($promise);
      };
      self.getMemberActivities = function(id) {
        courier.getMemberActivities(id).then(function(data) {
          self.member_activities = data;
        });
      };
      self.setMemberActivities = function(data) {
        var $promise = courier.setMemberActivities(data).then(function(data) {
          self.member_activities = data;
          // self.getMemberChances();
        });

        // Track the request and show its progress to the user.
        self.progress.addPromise($promise);
      };
      self.memberActivitiesCounts = function() {
        if (!self.member_activities) {
          self.member_activities_counts = null;
          return;
        }

        var count;
        self.member_activities_counts = {};
        for (var timestamp in self.member_activities) {
          self.member_activities_counts[timestamp] = {};
          for (var activity_group in self.activity_groups) {
            count = 0;
            for (var activity in self.activity_groups[activity_group].activities) {
              if (self.member_activities[timestamp][self.activity_groups[activity_group].activities[activity].id]) {
                count++;
              }
            }
            self.member_activities_counts[timestamp][self.activity_groups[activity_group].id] = count;
          }
        }
      };

      self.getMemberPrize = function() {
        return courier.getMemberPrize().then(function(data) {
          $timeout(function() {
            self.member_chances = data;
          }, 3000);
          return data;
        });
      };

      self.getRecentWinners = function() {
        courier.getRecentWinners().then(function(data) {
          self.recent_winners = data;
        });
      };

      self.getTodaysInsight = function() {
        courier.getTodaysInsight().then(function(data) {
          self.todays_insight = data;
        });
      };
      // self.getTodaysInsight();

      self.memberCookieRemove = function() {
        $cookies.remove('Drupal.visitor.ymca_retention_member', { path: '/' });
      };

    });
  };

})(jQuery);

/**
 * @file
 * Processes the FullCalendar options and passes them to the integration.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.fullcalendar.plugins.fullcalendar = {
    options: function (fullcalendar, settings) {
      if (settings.ajax) {
        fullcalendar.submitInit(settings);
      }

      var options = {
        eventClick: function (calEvent, jsEvent, view) {
          if (settings.sameWindow) {
            window.open(calEvent.url, '_self');
          }
          else {
            window.open(calEvent.url);
          }
          return false;
        },
        drop: function (date, allDay, jsEvent, ui) {
          for (var $plugin in Drupal.fullcalendar.plugins) {
            if (Drupal.fullcalendar.plugins.hasOwnProperty($plugin) && $.isFunction(Drupal.fullcalendar.plugins[$plugin].drop)) {
              try {
                Drupal.fullcalendar.plugins[$plugin].drop(date, allDay, jsEvent, ui, this, fullcalendar);
              }
              catch (exception) {
                alert(exception);
              }
            }
          }
        },
        events: function (start, end, timezone, callback) {
          // Fetch new items from Views if possible.
          if (settings.ajax && settings.fullcalendar_fields) {
            fullcalendar.dateChange(settings.fullcalendar_fields);
            if (fullcalendar.navigate) {
              if (!fullcalendar.refetch) {
                fullcalendar.fetchEvents();
              }
              fullcalendar.refetch = false;
            }
          }

          fullcalendar.parseEvents(callback);

          if (!fullcalendar.navigate) {
            // Add events from Google Calendar feeds.
            for (var entry in settings.gcal) {
              if (settings.gcal.hasOwnProperty(entry)) {
                fullcalendar.$calendar.find('.fullcalendar').fullCalendar('addEventSource',
                  $.fullCalendar.gcalFeed(settings.gcal[entry][0], settings.gcal[entry][1])
                );
              }
            }
          }

          // Set navigate to true which means we've starting clicking on
          // next and previous buttons if we re-enter here again.
          fullcalendar.navigate = true;
        },
        eventDrop: function (event, dayDelta, minuteDelta, allDay, revertFunc) {
          // @todo Remove once http://drupal.org/node/1915752 is resolved.
          var index = parseInt(event.index, 10) + 1;
          $.post(
            drupalSettings.path.basePath + 'fullcalendar/ajax/update/drop/' + event.entity_type + '/' + event.eid + '/' + event.field + '/' + index,
            'day_delta=' + dayDelta + '&minute_delta=' + minuteDelta + '&dom_id=' + event.dom_id,
            fullcalendar.update
          );
          return false;
        },
        eventResize: function (event, dayDelta, minuteDelta, revertFunc) {
          $.post(
            drupalSettings.path.basePath + 'fullcalendar/ajax/update/drop/' + event.entity_type + '/' + event.eid + '/' + event.field + '/' + event.index,
            'day_delta=' + dayDelta + '&minute_delta=' + minuteDelta + '&dom_id=' + event.dom_id,
            fullcalendar.update
          );
          return false;
        }
      };

      // Merge in our settings.
      $.extend(options, settings.fullcalendar);

      // Pull in overrides from URL.
      if (settings.date) {
        $.extend(options, settings.date);
      }

      return options;
    }
  };

})(jQuery, Drupal, drupalSettings);

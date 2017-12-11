This module requires the 3rd party library for FullCalendar located at http://arshaw.com/fullcalendar.

Download the most recent version of the plugin. When unzipped, the plugin
contains several directories. The fullcalendar/fullcalendar directory should be
moved to /libraries/fullcalendar i.e. in the root of your Drupal installation.
(e.g., /libraries/fullcalendar/fullcalendar.min.js). Do not include
the demos or jQuery directories.

Note: The location of the Fullcalendar library may change depending on the issue "Best practices for handling external libraries in Drupal 8" https://www.drupal.org/node/2605130

To use the FullCalendar module:

  1) Enable Views, Date, Date Range (experimental) modules
  2) Create a new entity that has a date field
  3) Create a view and add filters for the entity
  4) In the "Format" section, change the "Format" to "FullCalendar"
  5) Optionally, enable the "Use AJAX" option under "Advanced"

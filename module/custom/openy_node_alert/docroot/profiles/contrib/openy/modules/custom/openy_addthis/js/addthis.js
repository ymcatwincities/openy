/**
 * @file
 * AddThis script.
 */
var imported = document.createElement( 'script' );
imported.src = '//s7.addthis.com/js/300/addthis_widget.js#pubid=' + drupalSettings.addThisId;
document.head.appendChild( imported );

/**
 * @file
 * Instant games: scratch card.
 */

var doc = document,
    cvs = doc.getElementById('j-cvs'), ctx,
    config = {
      w: 400,
      h: 360
    },
    mouseDown = false;

var debug = function(msg){
  var obj = doc.getElementById('debug');
  obj.innerHTML += msg + '<br>';
};

function getLocalCoords(elem, ev) {
  var ox = 0, oy = 0;
  var first;
  var pageX, pageY;
  // currentTarget element.
  while (elem != null) {
    ox += elem.offsetLeft;
    oy += elem.offsetTop;
    elem = elem.offsetParent;
  }
  // fix,<=IE8
  if ("changedTouches" in ev) {
    first = ev.changedTouches[0];
    pageX = first.pageX;
    pageY = first.pageY;
  } else {
    pageX = ev.pageX;
    pageY = ev.pageY;
  }
  return { 'x': pageX - ox, 'y': pageY - oy };
}
function diffTransSize(cxt, threshold, callback) {
  if (!'getImageData' in ctx){
    return; // <=IE8
  }
  threshold = threshold || 0.5;
  if (threshold >1 || threshold < 0) {
    threshold = 1;
  }
  var imageData = ctx.getImageData(0, 0, cvs.width, cvs.height),
      pix = imageData.data,
      pixLength = pix.length,
      pixelSize = pixLength*0.25;
  var i = 1, k, l=0;
  for (; i <= pixelSize; i++) { // 3, 7, 11 -> 4n-1
    if (0 === pix[4*i-1]) l++;
  };
  if (l>pixelSize * threshold) {
    callback.apply(ctx, [l]);
  };
}
function scratchLine(cvs, x, y, fresh) {
  ctx = cvs.getContext('2d');
  // samsung Android 4.1.2, 4.2.2 default browser does not render, https://goo.gl/H5lwgo
  ctx.globalCompositeOperation = 'destination-out';

  ctx.lineWidth = 45;
  ctx.lineCap = ctx.lineJoin = 'round';
  ctx.strokeStyle = 'rgba(0,0,0,1)'; //'#000';
  if (fresh) {
    ctx.beginPath();
    // bug WebKit/Opera/IE9: +0.01
    ctx.moveTo(x+0.1, y);
  }
  ctx.lineTo(x, y);
  ctx.stroke();
  // fix samsung bug
  var style = cvs.style; // cursor/lineHeight
  style.lineHeight = style.lineHeight == '1' ? '1.1' : '1';

  diffTransSize(ctx, 0.5, function() {
    document.getElementById('title').innerHTML = Drupal.t('50% complete');
  });
}
function setupCanvases() {
  cvs.width = config.w;
  cvs.height = config.h;
  var ctx = cvs.getContext("2d");
  // add mask
  ctx.fillStyle = '#CCC';
  ctx.fillRect(0, 0, cvs.width, cvs.height);
  // On mouse down
  var mousedown_handler = function(e) {
    var local = getLocalCoords(cvs, e);
    mouseDown = true;
    scratchLine(cvs, local.x, local.y, true);
    // debug('touchstart')
    if (e.cancelable) {
      e.preventDefault();
    }
    return false;
  };
  // On mouse move
  var mousemove_handler = function(e) {
    // debug('touchmove')
    if (!mouseDown) { return true; }
    var local = getLocalCoords(cvs, e);
    // debug(local.x + ',' + local.y);
    scratchLine(cvs, local.x, local.y, false);

    if (e.cancelable) { e.preventDefault(); }
    return false;
  };
  // On mouseup
  var mouseup_handler = function(e) {
    // debug('touchend')
    if (mouseDown) {
      mouseDown = false;
      if (e.cancelable) { e.preventDefault(); }
      return false;
    }
    return true;
  };
  on(cvs, 'mousedown', mousedown_handler);
  on(cvs, 'touchstart', mousedown_handler);
  on(window, 'mousemove', mousemove_handler);
  on(window, 'touchmove', mousemove_handler);
  on(window, 'mouseup', mouseup_handler);
  on(window, 'touchend', mouseup_handler);
}
function on(E, N, FN){
  E.addEventListener ? E.addEventListener(N, FN, !1) : E.attachEvent('on' + N, FN);
}

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.openyCampaignScratchcard = {
    attach: function (context) {
      setupCanvases();
    }
  };

})(jQuery, Drupal, drupalSettings);

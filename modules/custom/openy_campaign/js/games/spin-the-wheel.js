(function ($, Drupal, drupalSettings) {

  'use strict';

  const TWO_PI = Math.PI * 2;
  const HALF_PI = Math.PI * 0.5;

  var devices = {
    mobile: {
      'maxWidth': 300,
      'ppm': 12,
      'viewHeight': 280,
    },
    tablet: {
      'maxWidth': 500,
      'ppm': 24,
      'viewHeight': 380,
    },
    desktop: {
      'maxWidth': 750,
      'ppm': 24,
      'viewHeight': 550,
    }
  };

  var colors = drupalSettings.openy_campaign.pallete;

  var _ppm = 24; // default value
  var _viewWidth = $('.spin-the-wheel-inner').innerWidth() - ($('.spin-the-wheel-inner').innerWidth() * 0.02);
  var _viewHeight = devices.desktop.viewHeight;
  if (window.innerWidth < 430) {
    _ppm = devices.mobile.ppm;
    _viewHeight = devices.mobile.viewHeight;
    //_viewWidth = devices.mobile.maxWidth;
  }
  else if (window.innerWidth < 760) {
    _ppm = devices.tablet.ppm;
    _viewHeight = devices.tablet.viewHeight;
    // _viewWidth = devices.tablet.maxWidth;
  }
  else if (window.innerWidth < 1024) {
    _ppm = devices.desktop.ppm;
    _viewHeight = devices.desktop.viewHeight;
    // _viewWidth = devices.desktop.maxWidth;
  }
  console.log(window.innerWidth);
  console.log(_viewHeight);

// canvas settings
  var viewWidth = _viewWidth,
    viewHeight = _viewHeight,
    viewCenterX = viewWidth * 0.5,
    viewCenterY = viewHeight * 0.5,
    drawingCanvas = document.getElementById("drawing_canvas"),
    ctx,
    timeStep = (1 / 60),
    time = 0;

  var ppm = _ppm, // pixels per meter
    physicsWidth = _viewWidth / ppm,
    physicsHeight = _viewHeight / ppm,
    physicsCenterX = physicsWidth * 0.5;
  var world;

  var wheel,
    arrow,
    mouseBody;

  var arrowMaterial,
    pinMaterial,
    contactMaterial;

  var wheelSpinning = false,
    wheelStopped = true;

  var particles = [];

  var statusLabel = document.getElementById('status_label');

  window.onload = function () {
    initDrawingCanvas();
    initPhysics();
    requestAnimationFrame(loop);

    $('.spin-the-wheel-inner .start').on('click', function (e) {
      $(this).hide();
      $('.load-bar').show();
      $('.centered').css('opacity', '1');
      wheelSpinning = true;
      wheelStopped = false;
      initDrawingCanvas();
      initPhysics();

      requestAnimationFrame(loop);
    });

  };

  function initDrawingCanvas() {
    drawingCanvas.width = _viewWidth + (_viewWidth * 0.2);
    drawingCanvas.height = _viewHeight + (_viewHeight * 0.2);
    ctx = drawingCanvas.getContext('2d');
  }

  function initPhysics() {
    world = new p2.World();
    world.solver.iterations = 100;
    world.solver.tolerance = 0;

    arrowMaterial = new p2.Material();
    pinMaterial = new p2.Material();
    contactMaterial = new p2.ContactMaterial(arrowMaterial, pinMaterial, {
      friction: 0.0,
      restitution: 0.1
    });
    world.addContactMaterial(contactMaterial);

    var wheelRadius = 8,
      wheelX = physicsCenterX,
      wheelY = wheelRadius + 4,
      arrowX = wheelX,
      arrowY = wheelY + wheelRadius + 0.625;

    wheel = new Wheel(wheelX, wheelY, wheelRadius, 32, 0.25, 7.5);
    wheel.body.angle = (Math.PI / 32.5);
    wheel.body.angularVelocity = 5;
    arrow = new Arrow(arrowX, arrowY, 0.5, 1.5);
    mouseBody = new p2.Body();

    world.addBody(mouseBody);
  }

  function spawnPartices() {
    for (var i = 0; i < 200; i++) {
      var p0 = new Point(viewCenterX, viewCenterY - 64);
      var p1 = new Point(viewCenterX, 0);
      var p2 = new Point(Math.random() * viewWidth, Math.random() * viewCenterY);
      var p3 = new Point(Math.random() * viewWidth, viewHeight + 64);

      particles.push(new Particle(p0, p1, p2, p3));
    }
  }

  function update() {
    particles.forEach(function (p) {
      p.update();
      if (p.complete) {
        particles.splice(particles.indexOf(p), 1);
      }
    });

    // p2 does not support continuous collision detection :(
    // but stepping twice seems to help
    // considering there are only a few bodies, this is ok for now.
    world.step(timeStep * 0.5);
    world.step(timeStep * 0.5);

    if (wheelSpinning === true && wheelStopped === false &&
      wheel.body.angularVelocity < 1 && arrow.hasStopped()) {
      wheelStopped = true;
      wheelSpinning = false;
      wheel.body.angularVelocity = 0;

      $('.spin-the-wheel-inner .start').show();
      $('.spin-the-wheel-inner').hide();
      $('.game-result').show();
      $('.load-bar').hide();
      $('.centered').css('opacity', '0.5');
    }
  }

  function draw() {
    ctx.clearRect(0, 0, viewWidth, viewHeight);

    wheel.draw();
    arrow.draw();

    particles.forEach(function (p) {
      p.draw();
    });
  }

  function loop() {
    update();
    draw();

    requestAnimationFrame(loop);
  }

/////////////////////////////
// wheel of fortune
/////////////////////////////
  function Wheel(x, y, radius, segments, pinRadius, pinDistance) {
    this.x = x;
    this.y = y;
    this.radius = radius;
    this.segments = segments;
    this.pinRadius = pinRadius;
    this.pinDistance = pinDistance;

    this.pX = this.x * ppm;
    this.pY = (physicsHeight - this.y) * ppm;
    this.pRadius = this.radius * ppm;
    this.pPinRadius = this.pinRadius * ppm;
    this.pPinPositions = [];

    this.deltaPI = TWO_PI / this.segments;

    this.createBody();
    this.createPins();
  }

  Wheel.prototype = {
    createBody: function () {
      this.body = new p2.Body({mass: 1, position: [this.x, this.y]});
      this.body.angularDamping = 0.0;
      this.body.addShape(new p2.Circle(this.radius));
      this.body.shapes[0].sensor = true; //TODO use collision bits instead

      var axis = new p2.Body({position: [this.x, this.y]});
      var constraint = new p2.LockConstraint(this.body, axis);
      constraint.collideConnected = false;

      world.addBody(this.body);
      world.addBody(axis);
      world.addConstraint(constraint);
    },
    createPins: function () {
      var l = this.segments,
        pin = new p2.Circle(this.pinRadius);

      pin.material = pinMaterial;

      for (var i = 0; i < l; i++) {
        var x = Math.cos(i / l * TWO_PI) * this.pinDistance,
          y = Math.sin(i / l * TWO_PI) * this.pinDistance;

        this.body.addShape(pin, [x, y]);
        this.pPinPositions[i] = [x * ppm, -y * ppm];
      }
    },
    gotLucky: function () {
      var currentRotation = wheel.body.angle % TWO_PI,
        currentSegment = Math.floor(currentRotation / this.deltaPI);

      return (currentSegment % 2 === 0);
    },
    draw: function () {
      // TODO this should be cached in a canvas, and drawn as an image
      // also, more doodads
      ctx.save();
      ctx.translate(this.pX, this.pY);

      ctx.beginPath();
      ctx.fillStyle = colors.mainlight;
      ctx.arc(0, 0, this.pRadius + 24, 0, TWO_PI);
      ctx.fill();
      //ctx.fillRect(-12, 0, 24, 400);

      ctx.rotate(-this.body.angle);

      for (var i = 0; i < this.segments; i++) {
        ctx.fillStyle = (i % 2 === 0) ? colors.maindark : colors.mainmedium;
        ctx.beginPath();
        ctx.arc(0, 0, this.pRadius, i * this.deltaPI, (i + 1) * this.deltaPI);
        ctx.lineTo(0, 0);
        ctx.closePath();
        ctx.fill();
      }

      ctx.fillStyle = '#401911';

      this.pPinPositions.forEach(function (p) {
        ctx.beginPath();
        ctx.arc(p[0], p[1], this.pPinRadius, 0, TWO_PI);
        ctx.fill();
      }, this);

      ctx.restore();
    }
  };
/////////////////////////////
// arrow on top of the wheel of fortune
/////////////////////////////
  function Arrow(x, y, w, h) {
    this.x = x;
    this.y = y;
    this.w = w;
    this.h = h;
    this.verts = [];

    this.pX = this.x * ppm;
    this.pY = (physicsHeight - this.y) * ppm;
    this.pVerts = [];

    this.createBody();
  }

  Arrow.prototype = {
    createBody: function () {
      this.body = new p2.Body({mass: 1, position: [this.x, this.y]});
      this.body.addShape(this.createArrowShape());

      var axis = new p2.Body({position: [this.x, this.y]});
      var constraint = new p2.RevoluteConstraint(this.body, axis, {
        worldPivot: [this.x, this.y]
      });
      constraint.collideConnected = false;

      var left = new p2.Body({position: [this.x - 2, this.y]});
      var right = new p2.Body({position: [this.x + 2, this.y]});
      var leftConstraint = new p2.DistanceConstraint(this.body, left, {
        localAnchorA: [-this.w * 2, this.h * 0.25],
        collideConnected: false
      });
      var rightConstraint = new p2.DistanceConstraint(this.body, right, {
        localAnchorA: [this.w * 2, this.h * 0.25],
        collideConnected: false
      });
      var s = 32,
        r = 4;

      leftConstraint.setStiffness(s);
      leftConstraint.setRelaxation(r);
      rightConstraint.setStiffness(s);
      rightConstraint.setRelaxation(r);

      world.addBody(this.body);
      world.addBody(axis);
      world.addConstraint(constraint);
      world.addConstraint(leftConstraint);
      world.addConstraint(rightConstraint);
    },

    createArrowShape: function () {
      this.verts[0] = [0, this.h * 0.25];
      this.verts[1] = [-this.w * 0.5, 0];
      this.verts[2] = [0, -this.h * 0.75];
      this.verts[3] = [this.w * 0.5, 0];

      this.pVerts[0] = [this.verts[0][0] * ppm, -this.verts[0][1] * ppm];
      this.pVerts[1] = [this.verts[1][0] * ppm, -this.verts[1][1] * ppm];
      this.pVerts[2] = [this.verts[2][0] * ppm, -this.verts[2][1] * ppm];
      this.pVerts[3] = [this.verts[3][0] * ppm, -this.verts[3][1] * ppm];

      var shape = new p2.Convex(this.verts);
      shape.material = arrowMaterial;

      return shape;
    },
    hasStopped: function () {
      var angle = Math.abs(this.body.angle % TWO_PI);

      return (angle < 1e-3 || (TWO_PI - angle) < 1e-3);
    },
    update: function () {

    },
    draw: function () {
      ctx.save();
      ctx.translate(this.pX, this.pY);
      ctx.rotate(-this.body.angle);

      ctx.fillStyle = '#401911';

      ctx.beginPath();
      ctx.moveTo(this.pVerts[0][0], this.pVerts[0][1]);
      ctx.lineTo(this.pVerts[1][0], this.pVerts[1][1]);
      ctx.lineTo(this.pVerts[2][0], this.pVerts[2][1]);
      ctx.lineTo(this.pVerts[3][0], this.pVerts[3][1]);
      ctx.closePath();
      ctx.fill();

      ctx.restore();
    }
  };
/////////////////////////////
// your reward
/////////////////////////////
  var Particle = function (p0, p1, p2, p3) {
    this.p0 = p0;
    this.p1 = p1;
    this.p2 = p2;
    this.p3 = p3;

    this.time = 0;
    this.duration = 3 + Math.random() * 2;
    this.color = 'hsl(' + Math.floor(Math.random() * 360) + ',100%,50%)';

    this.w = 10;
    this.h = 7;

    this.complete = false;
  };
  Particle.prototype = {
    update: function () {
      this.time = Math.min(this.duration, this.time + timeStep);

      var f = Ease.outCubic(this.time, 0, 1, this.duration);
      var p = cubeBezier(this.p0, this.p1, this.p2, this.p3, f);

      var dx = p.x - this.x;
      var dy = p.y - this.y;

      this.r = Math.atan2(dy, dx) + HALF_PI;
      this.sy = Math.sin(Math.PI * f * 10);
      this.x = p.x;
      this.y = p.y;

      this.complete = this.time === this.duration;
    },
    draw: function () {
      ctx.save();
      ctx.translate(this.x, this.y);
      ctx.rotate(this.r);
      ctx.scale(1, this.sy);

      ctx.fillStyle = this.color;
      ctx.fillRect(-this.w * 0.5, -this.h * 0.5, this.w, this.h);

      ctx.restore();
    }
  };
  Point = function (x, y) {
    this.x = x || 0;
    this.y = y || 0;
  };
/////////////////////////////
// math
/////////////////////////////
  /**
   * easing equations from http://gizma.com/easing/
   * t = current time
   * b = start value
   * c = delta value
   * d = duration
   */
  var Ease = {
    inCubic: function (t, b, c, d) {
      t /= d;
      return c * t * t * t + b;
    },
    outCubic: function (t, b, c, d) {
      t /= d;
      t--;
      return c * (t * t * t + 1) + b;
    },
    inOutCubic: function (t, b, c, d) {
      t /= d / 2;
      if (t < 1) return c / 2 * t * t * t + b;
      t -= 2;
      return c / 2 * (t * t * t + 2) + b;
    },
    inBack: function (t, b, c, d, s) {
      s = s || 1.70158;
      return c * (t /= d) * t * ((s + 1) * t - s) + b;
    }
  };

  function cubeBezier(p0, c0, c1, p1, t) {
    var p = new Point();
    var nt = (1 - t);

    p.x = nt * nt * nt * p0.x + 3 * nt * nt * t * c0.x + 3 * nt * t * t * c1.x + t * t * t * p1.x;
    p.y = nt * nt * nt * p0.y + 3 * nt * nt * t * c0.y + 3 * nt * t * t * c1.y + t * t * t * p1.y;

    return p;
  }

})(jQuery, Drupal, drupalSettings);

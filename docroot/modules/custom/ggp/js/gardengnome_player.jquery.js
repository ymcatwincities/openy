/**
 * @file
 * jQuery plugin to dynamically include Pano2VR and Object2VR players.
 */
(function($){
  var defaults = {
    package: false,
    type: false,
    html5: false,
    flash: false,
    autoplay: false,
    skin: false,
    width: 400,
    height: 300
  };

  /**
   * jQuery plugin to attach the player to an element.
   * @param options
   *    - package: absolute url to the extracted package
   *    - type: pano2vr or object2vr
   *    - html5: boolean value indicating if the HTML5 should be used if possible.
   *    - flash: boolean value indicating if the Flash player is available in
   *             the provided package.
   *    - skin: boolean value if the package provides a skin.
   *    - autoplay: If true, player will be embedded on window load. Else on
   *                clicking the preview image.
   *    - width/height: the players dimensions
   */
  $.fn.gardengnomePlayer = function (options) {
    return this.each(function () {
      if (!$.data(this, 'gardengnome-player')) {
        $.data(this, 'gardengnome-player', new Plugin(this, options));
      }
    });
  };

  /**
   * Read settings from the elements attributes.
   */
  function readSettings(element) {
    var settings = {};
    var $el = $(element);
    $.each(defaults, function(key, value){
      settings[key] = $el.data(key);
    });
    return settings;
  }

  /**
   * Basic plugin constructor.
   */
  function Plugin (element, options) {
    this.element = element;
    this.settings = $.extend({}, defaults, readSettings(element), options);
    this.config = this.settings.package + '/' + (this.settings.type === 'object2vr' ? 'object.xml' : 'pano.xml');
    this.init()
  }

  /**
   * Global counter for unique inline plugin ids.
   */
  var inline_plugins = 0;

  Plugin.prototype = {
    /**
     * Initialize the plugin and post-load javascript files if necessary.
     */
    init: function() {
      var $el = $(this.element);
      this.id = 'gginline-' + (++inline_plugins);
      var me = this;
      $el.click(function() {
        $(this).unbind('click');
        me.load(function() {
          me.play();
        });
      });
      if (this.settings.autoplay) {
        $el.click();
      }
    },
    /**
     * Load the required resources.
     */
    load: function(callback) {
      var scripts = [];
      if (this.settings.html5) {
        scripts.push(this.settings.package + '/' + this.settings.type + '_player.js');
      }
      if (this.settings.skin) {
        scripts.push(this.settings.package + '/skin.js');
      }
      var me = this;
      var loader = new ScriptLoader(scripts, function() {
        me.player = window[me.settings.type + 'Player'];
        me.skin = me.settings.skin ? window[me.settings.type + 'Skin'] : false;
        callback();
      });
      loader.start();
    },
    /**
     * Start the player.
     */
    play: function() {
      var $el = $(this.element);
      $el.children().remove();
      this.width = this.settings.width;
      this.height = this.settings.height;

      // Outer element, providing responsive and max width.
      var $outer = $('<div></div>');
      $outer.css({
        'max-width': this.width + 'px',
        'width': '100%'
      });
      $el.append($outer);

      // Inner element, defining elements height.
      var $inner = $('<div></div>');
      $inner.css({
        'padding-bottom': ((this.height/this.width) * 100) + "%",
        'position': 'relative'
      });
      $outer.append($inner);

      var css3D = window.ggHasHtml5Css3D ? window.ggHasHtml5Css3D() : true;
      var webGL = window.ggHasWebGL ? window.ggHasWebGL() : true;

      if (this.settings.html5 && (css3D || webGL)) {
        $inner.attr('id', this.id);
        this.playerObject = new this.player(this.id);
        if (this.settings.skin) {
          this.skinObject = new this.skin(this.playerObject, this.settings.package + '/');
        }
        this.playerObject.readConfigUrl(this.config);
      }
      else if (this.settings.flash && swfobject.hasFlashPlayerVersion('9.0.0')) {
        // We need an additional holder element, since swfobject doesn't append
        // but replace the target.
        var $flashholder = $('<div></div>');
        $flashholder.attr('id', this.id);
        $inner.append($flashholder);

        var flashvars = {};
        if (this.settings.type == 'object2vr') {
          flashvars.objectxml = 'object.xml';
        }
        else {
          flashvars.panoxml = 'pano.xml';
        }
        if (this.settings.skin) {
          flashvars.skinxml = 'skin.xml';
        }

        var params = {
          quality: "high",
          bgcolor: "#ffffff",
          allowscriptaccess: "sameDomain",
          allowfullscreen: "true",
          base: this.settings.package + '/'
        };

        var attributes = {
          id: "pano",
          name: "pano",
          align: "middle"
        };

        var flashpath = this.settings.package + '/' + this.settings.type + '_player.swf';
        swfobject.embedSWF(flashpath, this.id,"100%", "100%","9.0.0", "",flashvars, params, attributes);
      }
    }
  };

  /**
   * Simple asynchronous script loader.
   */
  function ScriptLoader (scripts, callback) {
    this.scripts = {};
    var me = this;
    $.each(scripts, function(index, script){
      me.scripts[script] = false;
    });
    this.callback = callback;
  }

  ScriptLoader.prototype = {
    start: function() {
      var me = this;
      $.each(this.scripts, function(url, status) {
        $.getScript(url, function(){
          me.scripts[url] = true;
          me.checkComplete();
        });
      });
    },
    checkComplete: function() {
      var complete = true;
      $.each(this.scripts, function(url, status) {
        complete = complete && status;
      });
      if (complete) {
        this.callback();
      }
    }
  };

}(jQuery));

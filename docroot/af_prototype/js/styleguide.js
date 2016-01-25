(function (window, document, $) {
  $.getJSON("fonts/selection.json", function (data) {
    var items = [];

    $.each(data.icons, function (key, value) {
      items.push('<li>' +
        '<span class="icon_apeareance icon-' + value.properties.name + '"></span>' +
        '<span class="icon-class-name">icon-' + value.properties.name + '</span>' +
        '</li>');
    });

    $("<ul/>", {
      "class": "icon-list",
      html: items.join("")
    }).appendTo($('#icons'));
  });
}(this, this.document, this.jQuery));

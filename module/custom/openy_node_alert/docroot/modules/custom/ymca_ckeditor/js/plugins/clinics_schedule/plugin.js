/**
 * @file
 * Clinics Schedule plugin.
 *
 * @ignore
 */
(function($, Drupal, drupalSettings, CKEDITOR) {

    "use strict";

    CKEDITOR.plugins.add('clinics_schedule', {
        requires: 'widget,dialog',
        init: function(editor) {
            var maxGridColumns = 12;

            CKEDITOR.dialog.add('clinics_schedule', function(editor) {
                return {
                    title: Drupal.t('Clinics Schedule'),
                    minWidth: 200,
                    minHeight: 100,
                    contents: [{
                        id: 'info',
                        elements: [{
                            id: 'type',
                            type: 'select',
                            label: Drupal.t('Columns count'),
                            items: [
                                [1, '1'],
                                [2, '2'],
                                [3, '3'],
                                [4, '4'],
                                [5, '5']
                            ],
                            required: true,
                            setup: function(widget) {
                                this.setValue(widget.data.colCount !== undefined ? widget.data.colCount : 5);
                            },
                            commit: function(widget) {
                                widget.setData('colCount', this.getValue());
                            }
                        }]
                    }]
                };
            });

            // Add widget
            editor.ui.addButton('ClinicsSchedule', {
                label: Drupal.t('Clinics Schedule'),
                command: 'clinics_schedule',
                icon: this.path + 'clinics_schedule.png'
            });
            editor.widgets.add('clinics_schedule', {
                allowedContent: 'div(!clinics_schedule);',
                requiredContent: 'div(clinics_schedule)',
                parts: {
                    clinics_schedule: 'div.clinics_schedule',
                },
                editables: {
                    content: '',
                },
                template: '<div class="clinics_schedule">' +
                '</div>',
                dialog: 'clinics_schedule',
                // Before init.
                upcast: function(element) {
                    return element.name == 'div' && element.hasClass('clinics_schedule');
                },
                // Init function is useful after copy paste rebuild.
                init: function() {
                    this.createEditable(maxGridColumns);
                },
                // Prepare data
                data: function() {
                    if (this.data.colCount && this.element.getChildCount() < 1) {
                        var colCount = this.data.colCount;
                        var row = this.parts.clinics_schedule;
                        this.createGrid(colCount, row);
                    }
                },
                // Create grid
                createGrid: function(colCount, row) {
                    var content = '<div class="container">' +
                        '<div class="row">' +
                        '<h2>CLINICS SCHEDULE</h2>' +
                        '<h3>{{ subheader }}</h3>' +
                        '<div class="description">{{ description text }}</div>' +
                        '<h4>upcoming clinics:</h4>';
                    for (var i = 1; i <= colCount; i++) {
                        content = content + '<div class="columns column-' + i + '">' +
                            '<div class="img-wrapper">{{ img }}</div>' +
                            '  <h5>{{ title }}</h5>' +
                            '    <div class="date">{{ date }}</div>' +
                            '</div>';
                    }
                    content = content + '</div></div>';
                    row.appendHtml(content);
                    this.createEditable(colCount);
                },
                // Create editable.
                createEditable: function(colCount) {
                    this.initEditable('title1', {
                        selector: '.row h2'
                    });
                    this.initEditable('title2', {
                        selector: '.row h3'
                    });
                    this.initEditable('description', {
                        selector: '.description'
                    });
                    this.initEditable('title3', {
                        selector: '.row h4'
                    });
                    for (var i = 1; i <= colCount; i++) {
                        this.initEditable('h4' + i, {
                            selector: '.row > .column-' + i + ' h5'
                        });
                        this.initEditable('date' + i, {
                            selector: '.row > .column-' + i + ' .date'
                        });
                        this.initEditable('register-label' + i, {
                            selector: '.row > .column-' + i + ' .img-wrapper'
                        });
                    }
                }
            });
        }
    });

})(jQuery, Drupal, drupalSettings, CKEDITOR);

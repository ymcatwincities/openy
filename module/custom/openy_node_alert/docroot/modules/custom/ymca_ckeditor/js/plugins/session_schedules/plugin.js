/**
 * @file
 * Session Schedules plugin.
 *
 * @ignore
 */
(function($, Drupal, drupalSettings, CKEDITOR) {

    "use strict";

    CKEDITOR.plugins.add('session_schedules', {
        requires: 'widget,dialog',
        init: function(editor) {
            var maxGridColumns = 12;

            CKEDITOR.dialog.add('session_schedules', function(editor) {
                return {
                    title: Drupal.t('Session Schedules'),
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
            editor.ui.addButton('SessionSchedules', {
                label: Drupal.t('Session Schedules'),
                command: 'session_schedules',
                icon: this.path + 'session_schedules.png'
            });
            editor.widgets.add('session_schedules', {
                allowedContent: 'div(!session_schedules);',
                requiredContent: 'div(session_schedules)',
                parts: {
                    session_schedules: 'div.session_schedules',
                },
                editables: {
                    content: '',
                },
                template: '<div class="session_schedules">' +
                '</div>',
                dialog: 'session_schedules',
                // Before init.
                upcast: function(element) {
                    return element.name == 'div' && element.hasClass('session_schedules');
                },
                // Init function is useful after copy paste rebuild.
                init: function() {
                    this.createEditable(maxGridColumns);
                },
                // Prepare data
                data: function() {
                    if (this.data.colCount && this.element.getChildCount() < 1) {
                        var colCount = this.data.colCount;
                        var row = this.parts.session_schedules;
                        this.createGrid(colCount, row);
                    }
                },
                // Create grid
                createGrid: function(colCount, row) {
                    var content = '<div class="container"><div class="row"><h2>SESSION SCHEDULES</h2>';
                    for (var i = 1; i <= colCount; i++) {
                        content = content + '<div class="columns">' +
                            '  <h4>Session ' + i + ':</h4>' +
                            '    <div class="date">{{ date }}</div>' +
                            '    <div class="register-label">Registration opens:</div>' +
                            '    <div class="register-date">{{ date }}</div>' +
                            '</div>';
                    }
                    content = content + '</div></div>';
                    row.appendHtml(content);
                    this.createEditable(colCount);
                },
                // Create editable.
                createEditable: function(colCount) {
                    this.initEditable('title', {
                        selector: '.row h2'
                    });
                    for (var i = 1; i <= colCount; i++) {
                        this.initEditable('h4' + i, {
                            selector: '.row > .columns:nth-child(' + (i + 1) + ') h4'
                        });
                        this.initEditable('date' + i, {
                            selector: '.row > .columns:nth-child(' + (i + 1) + ') .date'
                        });
                        this.initEditable('register-label' + i, {
                            selector: '.row > .columns:nth-child(' + (i + 1) + ') .register-label'
                        });
                        this.initEditable('register-date' + i, {
                            selector: '.row > .columns:nth-child(' + (i + 1) + ') .register-date'
                        });
                    }
                }
            });
        }
    });

})(jQuery, Drupal, drupalSettings, CKEDITOR);

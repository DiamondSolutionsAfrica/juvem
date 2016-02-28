jQuery(document).ready(function () {

    var eHtml = function (value) {
        return $('<i></i>').text(value).html();
    };

    /*
     $('#nav').affix({
     offset: {
     top: $('#nav').offset().top
     }
     });
     */

    /**
     * Enable tooltips and popvers
     */
    $('[data-toggle="tooltip"]').tooltip({
        container: 'body'
    });
    //$('[data-toggle="popover"]').popover();

    /**
     * Admin event list table
     */
    $('#eventListTable').on('click-row.bs.table', function (e, row, $element) {
        location.href = row.eid;
    });

    /**
     * Admin event participants list table
     */
    $('#participantsListTable').on('click-row.bs.table', function (e, row, $element) {
        location.href = 'participation/' + row.pid;
    });

    /**
     * Handle via prototype injected forms
     */
    $('.prototype-container').each(function (index) {
        var element = $(this),
            prototype;
        if (element.attr('data-prototype')) {
            prototype = element.data('prototype');

            var addElementHandlers = function () {
                element.find('.prototype-remove').on('click', function (e) {
                    e.preventDefault();
                    $(this).parent().parent().parent().parent().remove();
                });
                element.find('[data-toggle="popover"]').popover({
                    container: 'body',
                    placement: 'top',
                    html: true,
                    trigger: 'focus'
                    /*
                     }).click(function (e) {
                     e.preventDefault();
                     $(this).popover('toggle');
                     */
                });
            };

            element.data('index', element.find('.prototype-element').length);
            element.find('.prototype-add').on('click', function (e) {
                e.preventDefault();
                var index = element.data('index');
                var newForm = prototype.replace(/__name__/g, index);
                element.data('index', index + 1);

                element.find('.prototype-elements').append(newForm);
                addElementHandlers();
            });
            addElementHandlers();
        }
    });

    $('*#mail-form input, *#mail-form textarea').change(function () {
        var content = {
                subject: $("input[name='app_bundle_event_mail[subject]']").val(),
                title: $("input[name='app_bundle_event_mail[title]']").val(),
                lead: $("input[name='app_bundle_event_mail[lead]']").val(),
                content: $("textarea[name='app_bundle_event_mail[content]']").val()
            },
            preview = $('*#mail-template iframe').contents(),
            exampleSalution = 'Frau',
            exampleLastName = 'Müller',
            exampleEventTitle = $("input[name='app_bundle_event_mail[eventTitle]']").val();

        var replacePlaceholders = function (value) {
            if (!value) {
                return '';
            }
            value = value.replace(/\{PARTICIPATION_SALUTION\}/g, exampleSalution);
            value = value.replace(/\{PARTICIPATION_NAME_LAST\}/g, exampleLastName);
            value = value.replace(/\{EVENT_TITLE\}/g, exampleEventTitle);

            return eHtml(value);
        };

        $.each(content, function (key, value) {
            value = replacePlaceholders(value);

            switch (key) {
                case 'content':
                    value = '<p>' + value.replace(/\n\n/g, '</p><p>') + '</p>';
                    break;
                case 'subject':
                    if (value == '') {
                        value = '<em>Kein Betreff</em>';
                    }
                    break;
            }

            if (key == 'subject') {
                $('*#mail-template-iframe-panel .panel-heading').html(value);
            } else {
                preview.find('#mail-part-' + key).html(value);
            }
        });

    });


});
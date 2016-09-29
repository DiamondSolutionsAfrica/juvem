$(function(){

    /**
     * EVENT: Handle via prototype injected forms
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

    /**
     * EVENT: Events participants email preview
     */
    var updateMailPreview = function () {
        var updateButton = $('*#mail-form .btn-update-preview');
        updateButton.prop('disabled', true);
        var content = {
                subject: $("#event_mail_subject").val(),
                title: $("#event_mail_title").val(),
                lead: $("#event_mail_lead").val(),
                content: $("#event_mail_content").val()
            },
            preview = $('*#mail-template iframe').contents(),
            exampleSalution = 'Frau',
            exampleLastName = 'Müller',
            exampleEventTitle = $('#mail-form').data('event-title');

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
                    value = value.replace(/\n\n/g, '</p><p>');
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
        updateButton.prop('disabled', false);
    };
    $('*#mail-form .btn-update-preview').click(updateMailPreview);
    $('*#mail-form input, *#mail-form textarea').change(updateMailPreview);


});
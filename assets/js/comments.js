$(document).ready(function () {
    $(document).on('submit', '#ticketCommentForm form.js-comment-form', function (e) {
        e.preventDefault();
        const $form = $(this);

        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method'),
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.ok) {
                    $('#ticketComments').html(res.listHtml);
                    $('#ticketCommentForm').html(res.formHtml);
                } else if (res.html) {
                    $('#ticketCommentForm').html(res.html);
                }
            },
            error: function () {
                $('#ticketCommentForm').prepend('<div class="text-danger mb-2">Failed to post.</div>');
            }
        });
    });
});
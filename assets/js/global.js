$(document).ready(function () {
    const $btn = $('.js-open-invites');
    if (!$btn.length) return;

    const countUrl = $btn.data('count-url');
    const listUrl = $btn.data('list-url');
    const $badge = $btn.find('.js-invites-count');

    $.ajax({url: countUrl, method: 'GET', dataType: 'json'})
        .done(res => {
            const n = res?.count ?? 0;
            if (n > 0) {
                $badge.text(n).removeClass('d-none');
            } else {
                $badge.addClass('d-none');
            }
        });

    $(document).on('click', '.js-open-invites', function () {
        const $content = $('#invitesModalContent');
        $content.html('<div class="modal-body text-center p-5"><div class="spinner-border text-primary"></div></div>');
        const modal = bootstrap.Modal.getOrCreateInstance('#invitesModal');
        modal.show();

        $.ajax({url: listUrl, method: 'GET', dataType: 'html'})
            .done(html => $content.html(html))
            .fail(() => $content.html('<div class="p-4 text-danger">Failed to load invites.</div>'));
    });

    $(document).on('click', '.js-accept-invite', function () {
        const $row = $(this).closest('.list-group-item');
        $.ajax({
            url: $row.data('accept-url'),
            method: 'POST',
            dataType: 'json',
            data: {_token: $row.data('token')},
        }).done(res => {
            if (res.ok) {
                $('#invitesModalContent').html(res.html);
                updateBadge(res.count);

                if ($('#projectsTableWrap').length) {
                    setTimeout(() => window.location.reload(), 50);
                }
            } else {
                alert(res.message || 'Accept failed.');
            }
        }).fail(xhr => alert(xhr.responseJSON?.message || 'Accept failed.'));
    });

    $(document).on('click', '.js-decline-invite', function () {
        const $row = $(this).closest('.list-group-item');
        $.ajax({
            url: $row.data('decline-url'),
            method: 'POST',
            dataType: 'json',
            data: {_token: $row.data('token')},
        }).done(res => {
            if (res.ok) {
                $('#invitesModalContent').html(res.html);
                updateBadge(res.count);
            } else {
                alert(res.message || 'Decline failed.');
            }
        }).fail(xhr => alert(xhr.responseJSON?.message || 'Decline failed.'));
    });

    function updateBadge(n) {
        if (n > 0) {
            $badge.text(n).removeClass('d-none');
        } else {
            $badge.addClass('d-none');
        }
    }
});
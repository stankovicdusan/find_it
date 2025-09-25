function initBoards() {
    const $lists = $('.ticket-list');

    try { $lists.sortable('destroy'); } catch(e) {}

    $lists
        .sortable({
            connectWith: '.ticket-list',
            items: '> .ticket-item',
            placeholder: 'ticket-placeholder',
            tolerance: 'pointer',
            forcePlaceholderSize: true,
            dropOnEmpty: true,
            revert: 120,
            appendTo: '#boardGrid',
            helper: 'original',
            cancel: '',
            zIndex: 10000,

            start: function (_e, ui) {
                ui.item.find('.ticket-card').removeAttr('data-bs-toggle data-bs-target');
            },
            stop: function (_e, ui) {
                const id = ui.item.find('.ticket-card').data('ticket-id');
                ui.item.find('.ticket-card')
                    .attr('data-bs-toggle', 'modal')
                    .attr('data-bs-target', '#ticketModal-' + id);
            },

            receive: function (_e, ui) { handleDrop($(this), ui); },
        });
}

function handleDrop($toList, ui) {
    const $fromList = ui.sender || ui.item.closest('.ticket-list');

    const toStatusId = parseInt($toList.data('statusId') ?? $toList.attr('data-status-id'), 10);

    let allowedRaw = $fromList.data('allowedTo') ?? $fromList.attr('data-allowed-to') ?? '';
    const allowedToIds = Array.isArray(allowedRaw)
        ? allowedRaw.map(Number)
        : String(allowedRaw).split(',').filter(Boolean).map(n => parseInt(n,10));

    const isAllowed = allowedToIds.includes(toStatusId);

    if (!isAllowed) {
        $fromList.sortable('cancel');
        flashError('Transition not allowed to this status!');

        return;
    }

    const ticketId = ui.item.find('.ticket-card').data('ticket-id');
    const moveUrl  = $toList.data('moveUrl') ?? $toList.attr('data-move-url');
    const csrf     = $toList.data('csrf')    ?? $toList.attr('data-csrf');

    const order = $toList.children('.ticket-item').map(function () {
        return $(this).find('.ticket-card').data('ticket-id');
    }).get();

    $.ajax({
        url: moveUrl,
        method: 'POST',
        dataType: 'json',
        data: { _token: csrf, ticketId, toStatusId, order },
        success: function (res) {
            if (!res || !res.ok) {
                $fromList.sortable('cancel');
                alert(res?.message || 'Move failed.');

                return;
            }

            const $fromBadge = $fromList.closest('.column').find('.badge');
            const $toBadge   = $toList.closest('.column').find('.badge');

            $fromBadge.text($fromList.find('.ticket-item').length);
            $toBadge.text($toList.find('.ticket-item').length);
        },
        error: function (xhr) {
            $fromList.sortable('cancel');
            alert(xhr.responseJSON?.message || 'Move failed.');
        }
    });
}

function flashError(msg) {
    const $box = $(`
                <div class="alert alert-danger shadow mb-2" role="alert">${msg}</div>
              `);
    $('#dndFlash').append($box);
    setTimeout(() => $box.fadeOut(180, () => $box.remove()), 1600);
}

$(document).ready(initBoards);
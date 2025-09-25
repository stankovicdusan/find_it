function debounce(fn, ms) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), ms);
    };
}

function initSearch()   {
    const $grid = $('#boardGrid');
    const searchUrl = $grid.data('search-url');

    function render(html) {
        $grid.html(html);
        if (typeof initBoards === 'function') initBoards();
        if (typeof initTooltips === 'function') initTooltips($grid[0]);
    }

    const runSearch = debounce(function () {
        const q = $('#boardSearch').val().trim();

        $.ajax({
            url: searchUrl,
            method: 'GET',
            data: {q},
            success: function (html) {
                render(html);
            },
            error: function () {
                render('<div class="p-4 text-danger">Search failed.</div>');
            }
        });
    }, 250);

    $(document).on('submit', '#boardSearchForm', function (e) {
        e.preventDefault();
        runSearch();
    });

    $(document).on('input', '#boardSearch', runSearch);

    $(document).on('click', '#boardSearchClear', function () {
        $('#boardSearch').val('');
        runSearch();
    });
}

function reloadTinymce() {
    if (window.tinymce) {
        try {
            tinymce.remove('.tinymce');
            tinymce.init({
                selector: '.tinymce',
                plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            });
        } catch (e) {}
    }
}

$(document).ready(function () {
    initSearch();

    $(document).on('click', '.js-create-ticket', function () {
        const url = $(this).data('url');
        const $modal = $('#createTicketModal');
        const $content = $('#createTicketModalContent');

        $content.html(`
          <div class="modal-body text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
          </div>
        `);
        $modal.modal('show');

        $.ajax({
            url: url,
            method: 'GET',
            success: function (html) {
                $content.html(html);

                reloadTinymce();
            },
            error: function () {
                $content.html('<div class="p-4 text-danger">Failed to load form.</div>');
            }
        });
    });

    $(document).on('submit', '#createTicketModalContent form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $content = $('#createTicketModalContent');

        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method'),
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.ok) {
                    const selector = `.ticket-list[data-status-id="${res.statusId}"]`;
                    const $list = $(selector);

                    if ($list.length) {
                        $list.html(res.ticketsHtml);
                        $list.closest('.column').find('.badge').text(res.badgeCount);

                        if (typeof initBoards === 'function') initBoards();
                    } else {
                        location.reload();
                        return;
                    }

                    $('#createTicketModal').modal('hide');
                } else if (res.html) {
                    $content.html(res.html);
                } else {
                    $content.html('<div class="p-4 text-danger">Unexpected response.</div>');
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.html) {
                    $content.html(xhr.responseJSON.html);
                } else {
                    $content.html('<div class="p-4 text-danger">An error occurred.</div>');
                }
            }
        });
    });

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

                reloadTinymce();
            },
            error: function () {
                $('#ticketCommentForm').prepend('<div class="text-danger mb-2">Failed to post.</div>');
            }
        });
    });

    $(document).on('click', '.js-ticket-status-choice', function (e) {
        e.preventDefault();

        const $item       = $(this);
        const toStatusId  = parseInt($item.data('to-status'), 10);
        const fromStatusId= parseInt($item.data('from-status'), 10);

        const $box     = $item.closest('#ticketStatusBox');
        const ticketId = parseInt($box.data('ticket-id'), 10);
        const moveUrl  = $box.data('move-url');
        const csrf     = $box.data('csrf');

        const $toList = $('.ticket-list[data-status-id="' + toStatusId + '"]');
        let order = [];
        if ($toList.length) {
            const current = $toList.children('.ticket-item').map(function () {
                return $(this).find('.ticket-card').data('ticket-id');
            }).get();
            order = [ticketId].concat(current.filter(id => id !== ticketId));
        }

        $.ajax({
            url: moveUrl,
            method: 'POST',
            dataType: 'json',
            data: { _token: csrf, ticketId, toStatusId, order }
        })
            .done(function (res) {
                if (!res || !res.ok) {
                    alert(res?.message || 'Update failed.');
                    return;
                }

                if ($toList.length) {
                    const $fromList = $('.ticket-list[data-status-id="' + fromStatusId + '"]');
                    const $itemCard = $('.ticket-card[data-ticket-id="' + ticketId + '"]').closest('.ticket-item');

                    if ($itemCard.length) {
                        $toList.prepend($itemCard);

                        const $fromBadge = $fromList.closest('.column').find('.badge').first();
                        const $toBadge   = $toList.closest('.column').find('.badge').first();

                        if ($fromList.length) $fromBadge.text($fromList.find('.ticket-item').length);
                        $toBadge.text($toList.find('.ticket-item').length);
                    } else {
                        const $grid = $('#boardGrid');
                        const url   = $grid.data('search-url');
                        if (url) $.get(url, { q: '' }).done(html => $grid.html(html));
                    }
                }

                const $card  = $('.js-open-ticket[data-ticket-id="' + ticketId + '"]');
                const modalUrl = $card.data('url');
                if (modalUrl) {
                    $('#ticketModalContent').html(
                        '<div class="modal-body text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>'
                    );
                    $.get(modalUrl).done(html => $('#ticketModalContent').html(html));
                }
            })
            .fail(function (xhr) {
                alert(xhr.responseJSON?.message || 'Update failed.');
            });
    });

    (function () {
        const $modal   = $('#ticketModal');
        const $content = $('#ticketModalContent');

        function setTicketParam(id) {
            const u = new URL(location.href);
            u.searchParams.set('ticket', id);
            history.pushState({}, '', u);
        }

        function removeTicketParam() {
            const u = new URL(location.href);
            u.searchParams.delete('ticket');
            history.replaceState({}, '', u);
        }

        function getTicketUrlById(id) {
            const $card = $('.js-open-ticket[data-ticket-id="' + id + '"]');
            if ($card.length) return $card.data('url');
        }

        function renderError() {
            $content.html('<div class="p-4 text-danger">Failed to load ticket.</div>');
        }

        function loadAndShow(id, url, pushParamAfterSuccess = true) {
            if (!url) return;

            $modal.modal('show');

            $.get(url)
                .done(function (html) {
                    $content.html(html);
                    if (typeof reloadTinymce === 'function') reloadTinymce();
                    if (pushParamAfterSuccess) setTicketParam(id);
                })
                .fail(renderError);
        }

        function openTicketById(id) {
            loadAndShow(id, getTicketUrlById(id));
        }

        $(document)
            .off('click.ticketModal', '.js-open-ticket')
            .on('click.ticketModal', '.js-open-ticket', function (e) {
                if ($('.ui-sortable-helper').length) return;
                e.preventDefault();

                const id = String($(this).data('ticket-id'));
                const url = $(this).data('url');
                loadAndShow(id, url);
            });

        $(function () {
            const id = new URL(location.href).searchParams.get('ticket');
            if (id) openTicketById(id);
        });

        $modal.on('hidden.bs.modal', removeTicketParam);
    })();
});

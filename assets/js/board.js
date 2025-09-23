$(document).off('click.ticketModal', '.js-open-ticket').on('click.ticketModal', '.js-open-ticket', function (e) {
    if ($('.ui-sortable-helper').length) return;

    e.preventDefault();
    const $card = $(this);
    const url = $card.data('url');
    const $modal = $('#ticketModal');
    const $content = $('#ticketModalContent');

    $content.html(`
         <div class="modal-body text-center p-5">
             <div class="spinner-border text-primary" role="status"></div>
         </div>
    `);

    $modal.modal('show');

    $.ajax({
        url: url,
        method: 'GET',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        success: function (html) {
            $content.html(html);

            if (window.tinymce) {
                try {
                    tinymce.remove('#ticketModalContent .tinymce');
                    tinymce.init({
                        selector: '#ticketModalContent .tinymce',
                        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                    });
                } catch (e) {
                }
            }
        },
        error: function () {
            $content.html('<div class="p-4 text-danger">Failed to load ticket.</div>');
        }
    });
});

function debounce(fn, ms) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), ms);
    };
}

function initSearch() {
    const $grid = $('#boardGrid');
    const searchUrl = $grid.data('search-url');

    function render(html) {
        $grid.html(html);
        if (typeof initBoards === 'function') initBoards();
        if (typeof initTooltips === 'function') initTooltips($grid[0]);
    }

    const runSearch = debounce(function () {
        const q = $('#boardSearch').val().trim();
        $grid.css('opacity', .6); // mali vizuelni hint
        $.ajax({
            url: searchUrl,
            method: 'GET',
            data: {q},
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function (html) {
                render(html);
            },
            error: function () {
                render('<div class="p-4 text-danger">Search failed.</div>');
            },
            complete: function () {
                $grid.css('opacity', 1);
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

$(document).ready(function () {
    initSearch();
});

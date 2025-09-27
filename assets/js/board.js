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

$(document).ready(function () {
    initSearch();
});

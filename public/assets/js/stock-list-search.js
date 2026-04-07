/**
 * Stock page: live search in the product table (first column = product name).
 *
 * The search box uses oninput="runStockTableSearch(this.value)" in the HTML.
 * Two modes:
 * - Empty search → show every row again, bring back pagination (initTablePagination from the table widget).
 * - Something typed → hide pagination for a sec, show only rows whose name contains that text (not case-sensitive).
 */

/**
 * The tbody mixes real product rows with a possible "empty table" row (one cell with colspan).
 * We only want the normal data rows for filtering.
 */
function getProductRowsOnly(tbody) {
    return Array.from(tbody.querySelectorAll('tr')).filter(function (row) {
        return !row.querySelector('td[colspan]');
    });
}

/**
 * Called every time the user types in the search field.
 * @param {string} rawSearchText - whatever is in the input right now
 */
function runStockTableSearch(rawSearchText) {
    var table = document.querySelector('table');
    if (!table) {
        return;
    }
    var tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }

    var rows = getProductRowsOnly(tbody);

    // Same page size the table widget uses (fallback 10 if the attribute is missing or weird)
    var wrapper = document.querySelector('[data-items-per-page]');
    var itemsPerPage = 10;
    if (wrapper) {
        var parsed = parseInt(wrapper.getAttribute('data-items-per-page'), 10);
        if (!isNaN(parsed) && parsed > 0) {
            itemsPerPage = parsed;
        }
    }

    var pagination = document.getElementById('table-pagination');
    var searchTextLower = (rawSearchText || '').trim().toLowerCase();

    // --- Search cleared: put the table back like before ---
    if (!searchTextLower) {
        rows.forEach(function (row) {
            // '' lets the row use normal CSS display again (table-row)
            row.style.display = '';
            row.classList.remove('hidden');
        });
        // initTablePagination is defined in another script; only call if it's there
        if (typeof initTablePagination === 'function') {
            initTablePagination('table', itemsPerPage);
        }
        return;
    }

    // --- User is filtering: pagination would be wrong, so hide it ---
    if (pagination) {
        pagination.style.display = 'none';
    }

    rows.forEach(function (row) {
        var firstCell = row.querySelector('td');
        var firstColumnText = firstCell ? firstCell.textContent.trim().toLowerCase() : '';
        // indexOf !== -1 means "search text appears anywhere inside the product name"
        var rowMatchesSearch = firstColumnText.indexOf(searchTextLower) !== -1;
        if (rowMatchesSearch) {
            row.style.display = '';
            row.classList.remove('hidden');
        } else {
            row.style.display = 'none';
        }
    });
}

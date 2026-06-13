document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.flash-bar, .toast').forEach(function (el) {
        setTimeout(function () { el.remove(); }, 4000);
    });

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    var toggle = document.querySelector('.nav-toggle');
    var nav = document.querySelector('.hnav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('nav-open');
        });
    }

    document.querySelectorAll('select[name="year"]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('year', sel.value);
            window.location.href = url.toString();
        });
    });
});

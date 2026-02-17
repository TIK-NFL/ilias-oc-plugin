// dropdown toggle for dynamically re-rendered tables (delegated handlers)
$(document).on("click", ".iliasopencast_dropdown_toggle", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $dd = $(this).closest(".dropdown");
    const $menu = $dd.find(".dropdown-menu").first();

    $(".dropdown-menu").not($menu).hide();
    $menu.toggle();
});

$(document).on("click", function () {
    $(".dropdown-menu").hide();
});

$(document).on("click", ".dropdown-menu", function (e) {
    e.stopPropagation();
});
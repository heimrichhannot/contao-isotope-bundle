(function($)
{
    window.huhIsotopeBackend =
    {
        init: function() {
            this.bookingOverviewNavigation();
        },
        bookingOverviewNavigation: function() {
            $("#huh_isotope_bookingoverview_prev, #huh_isotope_bookingoverview_next").on("click", function() {
                $('#huh_isotope_backend_product_booking_overview').load(this.href + " #huh_isotope_backend_product_booking_overview", function() {
                    $(this).children(':first').unwrap();
                    window.huhIsotopeBackend.bookingOverviewNavigation();
                });
                return false;
            })
        }
    }

    $(document).ready(function() {
        huhIsotopeBackend.init();
    });
})(jQuery);
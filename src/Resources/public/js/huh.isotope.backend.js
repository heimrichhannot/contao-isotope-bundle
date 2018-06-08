(function($)
{
    window.huhIsotopeBackend =
    {
        init: function() {
            $('.huh_isotope_backend_openBookingList').addEvent("click", function() {
                var modal = new SimpleModal({
                    'keyEsc': false, // see https://github.com/terminal42/contao-notification_center/issues/99
                    'width': opt.width,
                    'hideFooter': true,
                    'draggable': false,
                    'overlayOpacity': .5,
                    'closeButton': true,
                    'onShow': function () {
                        document.body.setStyle('overflow', 'hidden');
                    },
                    'onHide': function () {
                        document.body.setStyle('overflow', 'auto');
                    }
                });
                modal.show({
                    "title": "test",
                    "content": "Test"
                });
                return false;
            })
        },
        bookingPlanModal: function() {




        }
    }

    $(document).ready(function() {
        huhIsotopeBackend.init();
    });
})
let jQuery = require('jquery');

($ => {
    let isotopeBundle = {
        init: function () {
            // this.initPDFViewer();
            this.initBookingPlan();
            this.registerEvents();
        },
        registerEvents: function () {
            // $(document).keydown(function(e) {
            //     if (e.which == 13) {
            //         e.preventDefault();
            //         pageNum = parseInt($('#ctrl-pageNum_' + activeID).val());
            //
            //         if ($('#ctrl-pageNum_' + activeID + ':focus').length && pageNum <= pdfDoc.numPages && pageNum >= 1) {
            //             isotopeBundle.queueRenderPage(pageNum);
            //         }
            //     }
            // });
            //
            // $('#ctrl-prev_' + activeID).on('click', function() {
            //     isotopeBundle.onPrevPage();
            // });
            //
            // $('#ctrl-next_' + activeID).on('click', function() {
            //     isotopeBundle.onNextPage();
            // });
            //
            // $('.tabs').on('click', function() {
            //     activeID = $(this).data('target');
            //     pageNum = parseInt($('#ctrl-pageNum_' + activeID).val());
            //
            //     if (!$('#pdfViewer_' + activeID).hasClass('loaded')) {
            //         isotopeBundle.initPDFViewer();
            //     }
            // });


            $(document).on('change', '.quantity_container input',function(){
                isotopeBundle.updateBookingPlan($(this));
            });
        },
        initPDFViewer: function() {
            // activeID = $('.tabs_pdfViewer li.active').data('target');
            canvas = $('#pdfViewer_' + activeID)[0];
            ctx = canvas.getContext('2d');
            url = '/' + $('#pdfViewer_' + activeID).data('src');

            $(loader).appendTo('.pdfViewer-wrapper_' + activeID);

            // If absolute URL from the remote server is provided, configure the CORS
            // header on that server.

            isotopeBundle.getDocument(url).then(function(pdfDoc_) {
                pdfDoc = pdfDoc_;

                $('#pageCount_' + activeID)[0].textContent = pdfDoc.numPages;
                // Initial/first page rendering
                isotopeBundle.renderPage(pageNum);
                $(document).find('#loader').remove();
                $('#pdfViewer_' + activeID).addClass('loaded');
            });
        },
        /**
         * Get page info from document, resize canvas accordingly, and render page.
         * @param num Page number.
         */
        renderPage: function(num) {
            pageRendering = true;

            // Using promise to fetch the page
            pdfDoc.getPage(num).then(function(page) {
                var viewport = page.getViewport(scale);
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                // Render PDF page into canvas context
                var renderContext = {
                    canvasContext: ctx,
                    viewport: viewport,
                };
                var renderTask = page.render(renderContext);

                // Wait for rendering to finish
                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        // New page rendering is pending
                        isotopeBundle.renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });

            // Update page counters
            $('#ctrl-pageNum_' + activeID)[0].textContent = pageNum;
        },
        /**
         * If another page rendering in progress, waits until the rendering is
         * finised. Otherwise, executes rendering immediately.
         */
        queueRenderPage: function(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                isotopeBundle.renderPage(num);
            }
            isotopeBundle.updatePageNum(num);
        },
        /**
         * Displays previous page.
         */
        onPrevPage: function() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            isotopeBundle.queueRenderPage(pageNum);
        },
        /**
         * Displays next page.
         */
        onNextPage: function() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            isotopeBundle.queueRenderPage(pageNum);
        },
        /**
         * update displayed current page number
         * @param num
         */
        updatePageNum: function(num) {
            $('#ctrl-pageNum_' + activeID).val(num);
        },
        initBookingPlan: function() {
            let input = $(document).find('#bookingPlan'),
                blocked = input.data('blocked');

            isotopeBundle.initFlatpickr(blocked);
        },
        initFlatpickr: function(blocked) {
            flatpickr('#bookingPlan', {
                dateFormat: 'd.m.Y',
                minDate: 'today',
                mode: 'range',
                inline:true,
                locale:"de",
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    var date = dayElem.dateObj;

                    var dateString = isotopeBundle.getComparableDate(date.getTime());
                    $.each(blocked,function(key,value){
                        if(value == dateString) {
                            dayElem.className += ' disabled blocked';
                        }
                    });
                }
            });
        },
        updateBookingPlan: function(elem) {
            let url = $(document).find('.bookingPlan_container').data('update'),
                productId = $(document).find('.bookingPlan_container').data('productId'),
                qantity = elem.val();

            $.ajax({
                url:url,
                dataType:'JSON',
                method:'POST',
                data: {'productId':productId,'quantity' : qantity},
                success: function(data) {
                    if(undefined !== data.result.data.blocked) {
                        isotopeBundle.initFlatpickr(data.result.data.blocked);
                    }
                    else {
                        alert('Ein Fehler ist aufgetreten!');
                    }
                }
            });
        },
        getComparableDate: function(date) {
            date = date.toString().substring(0,10);
            date = parseInt(date);
            return date+7200;
        }
    };

    module.exports = isotopeBundle;

    $(document).ready(function () {
        isotopeBundle.init();
    });
})(jQuery);

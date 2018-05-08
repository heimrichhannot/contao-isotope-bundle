let $ = require('jquery');

(function($) {
    var pdfJS = {
        init: function() {

            // activeID = $('.tabs_pdfViewer li.active').data('target');
            canvas = $('#pdfViewer_' + activeID)[0];
            ctx = canvas.getContext('2d');
            url = '/' + $('#pdfViewer_' + activeID).data('src');

            $(loader).appendTo('.pdfViewer-wrapper_' + activeID);

            // If absolute URL from the remote server is provided, configure the CORS
            // header on that server.

            PDFJS.getDocument(url).then(function(pdfDoc_) {
                pdfDoc = pdfDoc_;

                $('#pageCount_' + activeID)[0].textContent = pdfDoc.numPages;
                // Initial/first page rendering
                pdfJS.renderPage(pageNum);
                $(document).find('#loader').remove();
                $('#pdfViewer_' + activeID).addClass('loaded');
            });

            pdfJS.registerEvents();
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
                        pdfJS.renderPage(pageNumPending);
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
                pdfJS.renderPage(num);
            }
            pdfJS.updatePageNum(num);
        },
        /**
         * Displays previous page.
         */
        onPrevPage: function() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            pdfJS.queueRenderPage(pageNum);
        },
        /**
         * Displays next page.
         */
        onNextPage: function() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            pdfJS.queueRenderPage(pageNum);
        },
        /**
         * update displayed current page number
         * @param num
         */
        updatePageNum: function(num) {
            $('#ctrl-pageNum_' + activeID).val(num);
        },
        registerEvents: function() {
            $(document).keydown(function(e) {
                if (e.which == 13) {
                    e.preventDefault();
                    pageNum = parseInt($('#ctrl-pageNum_' + activeID).val());

                    if ($('#ctrl-pageNum_' + activeID + ':focus').length && pageNum <= pdfDoc.numPages && pageNum >= 1) {
                        pdfJS.queueRenderPage(pageNum);
                    }
                }
            });

            $('#ctrl-prev_' + activeID).on('click', function() {
                pdfJS.onPrevPage();
            });

            $('#ctrl-next_' + activeID).on('click', function() {
                pdfJS.onNextPage();
            });

            $('.tabs').on('click', function() {
                activeID = $(this).data('target');
                pageNum = parseInt($('#ctrl-pageNum_' + activeID).val());

                if (!$('#pdfViewer_' + activeID).hasClass('loaded')) {
                    pdfJS.init();
                }
            });
        },
    };

    $(document).ready(function() {
        pdfJS.init();
    });
})(JQuery);
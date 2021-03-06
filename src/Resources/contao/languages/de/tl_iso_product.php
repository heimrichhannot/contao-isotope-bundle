<?php

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_iso_product']['initialStock']            = ['Anfangsbestand', 'Geben Sie hier den Anfangsbestand des Produkts ein.'];
$GLOBALS['TL_LANG']['tl_iso_product']['stock']                   = ['Bestand', 'Geben Sie hier den Bestand des Produkts ein.'];
$GLOBALS['TL_LANG']['tl_iso_product']['releaseDate']             = ['Erscheinungsdatum', 'Geben Sie hier Erscheinungsdatum des Produkts ein.'];
$GLOBALS['TL_LANG']['tl_iso_product']['maxOrderSize']            = ['Maximale Bestellmenge', 'Geben Sie hier die maximale Bestellmenge ein.'];
$GLOBALS['TL_LANG']['tl_iso_product']['setQuantity']             = ['Set', 'Geben Sie hier ein, wie viele Artikel zusammen im Set verkauft werden.'];
$GLOBALS['TL_LANG']['tl_iso_product']['overrideStockShopConfig'] = [
    'Bestandskonfiguration überschreiben',
    'Wählen Sie diese Option, um die Konfiguration des Bestands, die Sie im Produkttyp bzw. in der aktuellen Shop-Konfiguration gesetzt haben, zu überschreiben.',
];
$GLOBALS['TL_LANG']['tl_iso_product']['jumpTo']                  = ['Weiterleitungsseite', 'Wählen Sie hier die Weiterleitungsseite aus.'];
$GLOBALS['TL_LANG']['tl_iso_product']['addedBy']                 = ['Hinzugefügt durch', 'Tragen Sie hier ein, wer den Artikel hochgeladen hat.'];
$GLOBALS['TL_LANG']['tl_iso_product']['tag']                     = ['Schlagworte', 'Geben Sie bitte die Begriffe einzeln ein. (Kommas dienen NICHT zur Trennung der Begriffe.)'];
$GLOBALS['TL_LANG']['tl_iso_product']['createMultiImageProduct'] = [
    'Alle Bilder zu einem Produkt hinzufügen',
    'Wählen Sie diese Option, wenn alle Bilder aus dem Bildupload zu einem Produkt hinzugefügt werden sollen.',
];
$GLOBALS['TL_LANG']['tl_iso_product']['downloadCount']           = ['Downloads', ''];
$GLOBALS['TL_LANG']['tl_iso_product']['relevance']               = ['Beliebtheit', ''];
$GLOBALS['TL_LANG']['tl_iso_product']['licence']                 = [
    'Lizenz',
    'Wählen Sie hier die Lizenz aus, die für die Aufnahme gilt.',
    \HeimrichHannot\IsotopeBundle\Helper\ProductHelper::ISO_LICENCE_FREE      => 'frei',
    \HeimrichHannot\IsotopeBundle\Helper\ProductHelper::ISO_LICENCE_COPYRIGHT => 'Copyright angeben',
    \HeimrichHannot\IsotopeBundle\Helper\ProductHelper::ISO_LICENCE_LOCKED    => 'geschützt (lizenzpflichtig)',
];
$GLOBALS['TL_LANG']['tl_iso_product']['copyright']               = ['Copyright', 'Bitte geben Sie einen Copyright an.'];
$GLOBALS['TL_LANG']['tl_iso_product']['uploadedFiles']           = [
    'Bild hochladen',
    'Fügen Sie hier Bilder hinzu, die für den Upload genutzt werden sollen. Wenn Sie mehrere Bilder auswählen, wird für jedes Bild ein eigener Artikel erstellt. Die Artikel besitzen die gleichen Attribute.',
    'Datei(en) auswählen',
];

$GLOBALS['TL_LANG']['tl_iso_product']['uploadedDownloadFiles'] = [
    'Downloadelemente hochladen',
    'Fügen Sie hier Dateien hinzu, die als Downloadelemente für den Artikel genutzt werden sollen.',
    'Datei(en) auswählen',
];


$lang['bookingStart']         = ['Buchungszeitraum-Start', 'Wählen Sie hier den Beginn der Buchung aus.'];
$lang['bookingStop']          = ['Buchungszeitraum-Ende', 'Wählen Sie hier das Ende der Buchung aus.'];
$lang['bookingBlock']         = [
    'Artikel vor/nach Bestellung blockieren',
    'Tragen Sie hier die Anzahl der Tage ein die ein Artikel nach seiner Buchung gesperrt sein soll. Dies kann bspw. benötigt werden wenn ein Artikel für einen Buchungszeitraum gebucht wird und nach dem Buchungszeitraum aus logistischn Gründen für eine Zeit gesperrt ist.',
];
$lang['bookingReservedDates'] = [
    'Produkt-Reservierungen',
    'Sie können hier Zeiträume hinterlegen, für die das Produkt reserviert sein soll. Die angegebenen Daten werden in die Berechnung der gesperrten Tage des Produktes aufgenommen.',
];

$lang['useCount'] = ['Anzahl festlegen', 'Wählen Sie diese Option, wenn Sie die Anzahl der reservierten Produkte angeben wollen. Wenn Sie diese Option nicht wählen, werden alle auf Lager befindlichen Produkte reserviert.'];
$lang['count']    = ['Anzahl', 'Wählen Sie hier die Anzahl der zu reservierenden Produkte aus.'];
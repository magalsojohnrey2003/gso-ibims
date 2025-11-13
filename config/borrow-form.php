<?php

return [
    /**
     * Absolute path to the borrow request PDF template that contains the AcroForm fields.
     */
    // Updated default template to v2
    'template' => public_path('pdf/borrow_request_form_v2.pdf'),

    /**
     * Optional path where a prepared/uncompressed copy of the template can be stored.
     * When provided, the application will attempt to write a qpdf-processed version
     * of the template to this location and use it for subsequent renders.
     */
    'prepared_path' => env('BORROW_FORM_PREPARED_PATH', storage_path('app/templates/borrow_request_form_v2_prepared.pdf')),

    /**
     * Path to the qpdf binary. If left null, the service will attempt to locate qpdf
     * on the system PATH (using `which` or `where`). Supplying an explicit path is
     * recommended on Windows deployments.
     */
    'qpdf_path' => env('BORROW_FORM_QPDF_PATH'),
];


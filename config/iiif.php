<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IIIF Image API base URL
    |--------------------------------------------------------------------------
    |
    | The `id` embedded in the IIIF tile pyramid (info.json / dzsave) and used
    | to build image service URLs. This must be the public URL under which the
    | `iiif/` storage symlink is served. Override per-environment via IIIF_BASE_URL.
    |
    */
    'base_url' => env('IIIF_BASE_URL', 'https://api.arquigrafia.org.br/iiif'),
];

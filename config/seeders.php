<?php

return [
    /*
     * Whether the seeders lay down a sample picture for a table.
     *
     * Every image written through the media library is converted to WebP and
     * given its own responsive variants, so a picture for every row means
     * hundreds of conversions. The seeders write none by default; turn this on
     * to give each table one illustrated row instead.
     */
    'images' => env('SEED_IMAGES', false),
];

<?php
require __DIR__ . '/vendor/autoload.php';

use Jcupitt\Vips;

// Path to an image in your storage
$input = __DIR__ . '/storage/app/private/images/6ea9fd50-78ce-4f4e-b403-23b7881c7553/full/max/0/default.jpg';
$output = __DIR__ . '/storage/app/private/images/6ea9fd50-78ce-4f4e-b403-23b7881c7553';

try {
    $image = Vips\Image::newFromFile($input, ['access' => 'sequential']);
    $thumbnail = Vips\Image::thumbnail($input, 200, ['height' => 200]);
    $image->dzsave($output, [
        'layout' => 'iiif3',
        'id' => 'http://dev.arquigrafia.org/iiif'
    ]);
    $thumbnail->writeToFile($output . 'full/200,/0/default.jpg');

    echo "Thumbnail created at: $output\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
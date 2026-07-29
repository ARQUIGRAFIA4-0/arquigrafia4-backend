<?php

namespace App\Support;

use App\Models\VRACore\VRACImage;

/**
 * Post-processes the `info.json` that libvips `dzsave` writes for a IIIF3 tile
 * pyramid.
 *
 * Two corrections are applied in place:
 *   - `id` is rewritten from `config('iiif.base_url')`. libvips bakes the base
 *     URL at tile time, so it rots when the public host changes; rewriting it
 *     turns a host change into a re-run of the backfill command, not a re-tile.
 *   - a `sizes` array is injected from the derivatives we actually generate
 *     (thumb long-edge 300, mid long-edge 1024, plus the original), so a viewer
 *     can request whole-image sizes that really exist on disk.
 *
 * Only these known-good sizes are advertised. We must NOT compute sizes from the
 * tile scaleFactors — `dzsave` produces region tiles, not whole-image
 * `full/{w},{h}` derivatives, so any invented size would 404 in the viewer.
 *
 * The file stays static (served straight off disk by nginx) — only its contents
 * are corrected.
 */
class IiifInfo
{
    /**
     * Rewrite the image's on-disk info.json (id + sizes). Returns true if the
     * file existed and was written, false if there was nothing to patch.
     */
    public static function patch(VRACImage $image): bool
    {
        $path = $image->path('info', 'absolute');
        if (! is_file($path)) {
            return false;
        }

        $info = json_decode((string) file_get_contents($path), true);
        if (! is_array($info)) {
            return false;
        }

        $info['id'] = rtrim(config('iiif.base_url'), '/').'/'.$image->id;

        $sizes = self::sizesFor($image);
        if ($sizes !== []) {
            $info['sizes'] = $sizes;
        } else {
            unset($info['sizes']);
        }

        file_put_contents(
            $path,
            json_encode($info, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return true;
    }

    /**
     * IIIF `sizes` from the whole-image derivatives we actually create — thumb
     * (long edge 300) and mid (long edge 1024). These map to real files at
     * `full/{w},{h}/0/default.jpg`.
     *
     * The original is intentionally excluded: libvips serves it as `full/max`,
     * not `full/{w},{h}`, so advertising its literal pixel size would 404.
     * Deduplicated and ascending by width.
     *
     * @return array<int, array{width:int, height:int}>
     */
    private static function sizesFor(VRACImage $image): array
    {
        $out = [];
        foreach (['thumb', 'mid'] as $key) {
            $w = $image->sizes[$key]['width'] ?? null;
            $h = $image->sizes[$key]['height'] ?? null;
            if ($w && $h) {
                $out["{$w}x{$h}"] = ['width' => (int) $w, 'height' => (int) $h];
            }
        }

        $out = array_values($out);
        usort($out, fn ($a, $b) => $a['width'] <=> $b['width']);

        return $out;
    }
}

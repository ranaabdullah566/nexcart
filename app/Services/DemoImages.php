<?php

namespace App\Services;

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Sample pictures drawn with GD (no internet needed): pills, capsules, bottles and boxes on soft gradients for products and
 * categories, and wide gradient art for the hero / banner / call-to-action sections. Every media id is remembered
 * (setting demo.image_ids) so "remove" deletes exactly these pictures and never an uploaded one.
 */
class DemoImages
{
    private const S = 2;   // draw at 2x, then shrink: smooth edges without anti-aliased primitives

    /** @var list<int> */
    private array $ids = [];

    public function loaded(): bool
    {
        return ! empty(setting('demo.image_ids'));
    }

    /** @return array{categories:int, products:int, sections:int} pictures added (only to things that have none) */
    public function load(int $products = 60): array
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException(__('The PHP GD extension is needed to draw sample pictures.'));
        }
        if ($this->loaded()) {
            throw new \RuntimeException(__('Sample pictures are already added. Remove them first to add them again.'));
        }
        @set_time_limit(600);
        $n = ['categories' => 0, 'products' => 0, 'sections' => 0];

        foreach (Category::where('is_active', true)->whereDoesntHave('media')->whereHas('products', fn ($p) => $p->active())->orderBy('name')->get() as $c) {
            $this->attach($c, 'image', $this->category($c->name), 'cat-'.$c->id);
            $n['categories']++;
        }

        $q = Product::active()->whereDoesntHave('media')->orderByDesc('is_featured')->latest('id')->limit(max(0, min($products, 400)));
        foreach ($q->get() as $p) {
            $this->attach($p, 'images', $this->product($p->name, (string) ($p->specs['dosage_form'] ?? '')), 'prod-'.$p->id);
            $n['products']++;
        }

        $n['sections'] = $this->sections();

        return $n;
    }

    /** Wide pictures for hero / banner / call-to-action sections that have none. */
    public function sections(): int
    {
        $n = 0;
        foreach (HomeSection::whereIn('type', ['hero', 'banner', 'cta'])->whereDoesntHave('media')->get() as $s) {
            $this->attach($s, 'image', $this->banner($s->type), 'sec-'.$s->id);
            $n++;
        }
        $this->persist();

        return $n;
    }

    /** Remember the new media ids next to any already remembered. */
    private function persist(): void
    {
        setting()->set('demo.image_ids', array_values(array_unique(array_merge((array) setting('demo.image_ids'), $this->ids))));
        $this->ids = [];
    }

    /** @return int pictures removed */
    public function remove(): int
    {
        $ids = (array) setting('demo.image_ids');
        $n = Media::whereIn('id', $ids)->get()->each->delete()->count();
        setting()->set('demo.image_ids', null);

        return $n;
    }

    /** A drawn product picture saved as a temp file, for callers that attach it themselves (the sample medicines). */
    public function productFile(string $name, string $form, string $key): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        File::ensureDirectoryExists($dir = storage_path('app/tmp'));
        $file = $dir.'/demoimg-'.$key.'.jpg';
        $im = $this->product($name, $form);
        imagejpeg($im, $file, 88);
        imagedestroy($im);

        return $file;
    }

    private function attach($model, string $collection, \GdImage $im, string $key): void
    {
        File::ensureDirectoryExists($dir = storage_path('app/tmp'));
        $file = $dir.'/demoimg-'.$key.'.jpg';
        imagejpeg($im, $file, 88);
        imagedestroy($im);
        $this->ids[] = $model->addMedia($file)->toMediaCollection($collection)->id;   // moves the temp file
    }

    public function __destruct()
    {
        $this->ids && $this->persist();   // never lose track of pictures if a long run is cut short
    }

    // ---------------------------------------------------------------- drawing

    private function hue(string $seed): int
    {
        return crc32($seed) % 360;
    }

    private function rgb(int $h, float $s, float $v): array
    {
        $i = (int) floor($h / 60) % 6;
        $f = $h / 60 - floor($h / 60);
        [$p, $q, $t] = [$v * (1 - $s), $v * (1 - $f * $s), $v * (1 - (1 - $f) * $s)];
        [$r, $g, $b] = [[$v, $t, $p], [$q, $v, $p], [$p, $v, $t], [$p, $q, $v], [$t, $p, $v], [$v, $p, $q]][$i];

        return [(int) ($r * 255), (int) ($g * 255), (int) ($b * 255)];
    }

    private function canvas(int $w, int $h): \GdImage
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        return $im;
    }

    private function col(\GdImage $im, array $c, int $alpha = 0): int
    {
        return imagecolorallocatealpha($im, $c[0], $c[1], $c[2], $alpha);
    }

    private function gradient(\GdImage $im, int $w, int $h, int $hue, bool $diag = false): void
    {
        [$r1, $g1, $b1] = $this->rgb($hue, .16, .99);
        [$r2, $g2, $b2] = $this->rgb(($hue + 25) % 360, .42, .92);
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            imageline($im, 0, $y, $w, $y, imagecolorallocate($im, (int) ($r1 + ($r2 - $r1) * $t), (int) ($g1 + ($g2 - $g1) * $t), (int) ($b1 + ($b2 - $b1) * $t)));
        }
        mt_srand($hue);
        for ($k = 0; $k < 5; $k++) {
            $d = mt_rand((int) ($w * .25), (int) ($w * .7));
            imagefilledellipse($im, mt_rand(0, $w), mt_rand(0, $h), $d, $d, imagecolorallocatealpha($im, 255, 255, 255, mt_rand(108, 118)));
        }
    }

    private function down(\GdImage $big, int $w, int $h): \GdImage
    {
        $out = imagecreatetruecolor($w, $h);
        imagecopyresampled($out, $big, 0, 0, 0, 0, $w, $h, imagesx($big), imagesy($big));
        imagedestroy($big);

        return $out;
    }

    private function shadow(\GdImage $im, int $cx, int $cy, int $rw, int $rh): void
    {
        for ($i = 6; $i >= 1; $i--) {
            imagefilledellipse($im, $cx, $cy, (int) ($rw * (1 + $i * .06)), (int) ($rh * (1 + $i * .22)), imagecolorallocatealpha($im, 30, 50, 90, 122 - $i));
        }
    }

    private function roundRect(\GdImage $im, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($im, $cx, $cy, $r * 2, $r * 2, $color);
        }
    }

    /** A rotated sprite drawn on its own transparent layer, then placed on $im. */
    private function sprite(\GdImage $im, int $cx, int $cy, int $size, float $angle, callable $draw): void
    {
        $layer = imagecreatetruecolor($size, $size);
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        imagefill($layer, 0, 0, imagecolorallocatealpha($layer, 0, 0, 0, 127));
        imagealphablending($layer, true);
        $draw($layer, $size);
        $rot = imagerotate($layer, $angle, imagecolorallocatealpha($layer, 0, 0, 0, 127));
        imagesavealpha($rot, true);
        imagecopy($im, $rot, (int) ($cx - imagesx($rot) / 2), (int) ($cy - imagesy($rot) / 2), 0, 0, imagesx($rot), imagesy($rot));
        imagedestroy($layer);
        imagedestroy($rot);
    }

    private function capsule(\GdImage $l, int $s, int $hue): void
    {
        $w = (int) ($s * .82);
        $h = (int) ($s * .34);
        $x = (int) (($s - $w) / 2);
        $y = (int) (($s - $h) / 2);
        $r = (int) ($h / 2);
        $a = $this->col($l, $this->rgb($hue, .75, .9));
        $b = $this->col($l, [250, 250, 252]);
        $this->roundRect($l, $x, $y, $x + $w, $y + $h, $r, $b);
        $this->roundRect($l, $x, $y, (int) ($x + $w / 2), $y + $h, $r, $a);
        imagefilledrectangle($l, (int) ($x + $w / 2 - $r), $y, (int) ($x + $w / 2), $y + $h, $a);
        imagefilledrectangle($l, (int) ($x + $w / 2), $y, (int) ($x + $w / 2 + $r * .1), $y + $h, $this->col($l, [0, 0, 0], 100));
        imagefilledrectangle($l, $x + (int) ($r * .5), $y + (int) ($h * .14), $x + $w - (int) ($r * .5), $y + (int) ($h * .26), $this->col($l, [255, 255, 255], 92));   // gloss
    }

    private function tablet(\GdImage $l, int $s, int $hue): void
    {
        $d = (int) ($s * .7);
        $c = (int) ($s / 2);
        imagefilledellipse($l, $c, $c + 6, $d, $d, $this->col($l, [40, 60, 100], 108));
        imagefilledellipse($l, $c, $c, $d, $d, $this->col($l, [250, 250, 252]));
        imagefilledellipse($l, $c, $c, (int) ($d * .84), (int) ($d * .84), $this->col($l, [238, 241, 246]));
        imagesetthickness($l, max(4, (int) ($s * .018)));
        imageline($l, (int) ($c - $d * .38), $c, (int) ($c + $d * .38), $c, $this->col($l, $this->rgb($hue, .3, .72)));
        imagesetthickness($l, 1);
    }

    private function bottle(\GdImage $im, int $w, int $h, int $hue): void
    {
        $cx = (int) ($w / 2);
        $bw = (int) ($w * .42);
        $bh = (int) ($h * .5);
        $top = (int) ($h * .3);
        $this->shadow($im, $cx, (int) ($top + $bh + $h * .03), (int) ($bw * 1.1), (int) ($h * .05));
        $glass = $this->col($im, $this->rgb($hue, .7, .78));
        $this->roundRect($im, $cx - (int) ($bw / 2), $top, $cx + (int) ($bw / 2), $top + $bh, (int) ($bw * .12), $glass);
        $this->roundRect($im, $cx - (int) ($bw * .22), (int) ($top - $h * .06), $cx + (int) ($bw * .22), $top + 4, (int) ($bw * .04), $glass);
        $this->roundRect($im, $cx - (int) ($bw * .27), (int) ($top - $h * .14), $cx + (int) ($bw * .27), (int) ($top - $h * .05), (int) ($bw * .04), $this->col($im, [250, 250, 252]));
        $this->roundRect($im, $cx - (int) ($bw * .42), (int) ($top + $bh * .3), $cx + (int) ($bw * .42), (int) ($top + $bh * .78), (int) ($bw * .05), $this->col($im, [252, 252, 254]));
        $this->cross($im, $cx, (int) ($top + $bh * .54), (int) ($bw * .2), $this->col($im, $this->rgb($hue, .75, .78)));
        imagefilledrectangle($im, $cx - (int) ($bw * .4), (int) ($top + $bh * .05), $cx - (int) ($bw * .34), (int) ($top + $bh * .9), $this->col($im, [255, 255, 255], 96));
    }

    private function box(\GdImage $im, int $w, int $h, int $hue): void
    {
        $cx = (int) ($w / 2);
        $bw = (int) ($w * .46);
        $bh = (int) ($h * .56);
        $x1 = $cx - (int) ($bw / 2);
        $y1 = (int) ($h * .24);
        $this->shadow($im, $cx, $y1 + $bh + (int) ($h * .02), (int) ($bw * 1.15), (int) ($h * .05));
        $main = $this->col($im, [251, 251, 253]);
        $this->roundRect($im, $x1, $y1, $x1 + $bw, $y1 + $bh, (int) ($bw * .05), $main);
        imagefilledrectangle($im, $x1, $y1, $x1 + $bw, (int) ($y1 + $bh * .26), $this->col($im, $this->rgb($hue, .72, .82)));
        imagefilledrectangle($im, $x1, (int) ($y1 + $bh * .9), $x1 + $bw, $y1 + $bh, $this->col($im, $this->rgb($hue, .72, .82)));
        $this->cross($im, $cx, (int) ($y1 + $bh * .56), (int) ($bw * .17), $this->col($im, $this->rgb($hue, .72, .82)));
        imagefilledrectangle($im, $x1 + (int) ($bw * .2), (int) ($y1 + $bh * .74), $x1 + (int) ($bw * .8), (int) ($y1 + $bh * .77), $this->col($im, [205, 212, 224]));
        imagefilledrectangle($im, $x1 + (int) ($bw * .3), (int) ($y1 + $bh * .8), $x1 + (int) ($bw * .7), (int) ($y1 + $bh * .83), $this->col($im, [220, 226, 236]));
    }

    private function cross(\GdImage $im, int $cx, int $cy, int $r, int $color): void
    {
        $t = (int) ($r * .42);
        imagefilledrectangle($im, $cx - $r, $cy - $t, $cx + $r, $cy + $t, $color);
        imagefilledrectangle($im, $cx - $t, $cy - $r, $cx + $t, $cy + $r, $color);
    }

    private function kind(string $name, string $form = ''): string
    {
        $t = mb_strtolower($form.' '.$name);

        return match (true) {
            (bool) preg_match('/syrup|suspension|liquid|drops|solution|lotion|spray|sachet|ors\b|oral/', $t) => 'bottle',
            (bool) preg_match('/capsule|cap\b|softgel/', $t) => 'capsule',
            (bool) preg_match('/tablet|tab\b|tabs|pill|effervescent/', $t) => 'tablet',
            default => 'box',
        };
    }

    // ---------------------------------------------------------------- pictures

    private function product(string $name, string $form = ''): \GdImage
    {
        $S = self::S;
        $w = 640 * $S;
        $hue = $this->hue($name);
        $im = $this->canvas($w, $w);
        $this->gradient($im, $w, $w, $hue);
        $cx = (int) ($w / 2);

        switch ($this->kind($name, $form)) {
            case 'bottle':
                $this->bottle($im, $w, $w, $hue);
                break;
            case 'capsule':
                $this->shadow($im, $cx, (int) ($w * .66), (int) ($w * .5), (int) ($w * .05));
                $this->sprite($im, $cx, (int) ($w * .5), $w, 32, fn ($l, $s) => $this->capsule($l, $s, $hue));
                $this->sprite($im, (int) ($w * .42), (int) ($w * .66), (int) ($w * .8), -18, fn ($l, $s) => $this->capsule($l, $s, ($hue + 40) % 360));
                break;
            case 'tablet':
                $this->shadow($im, $cx, (int) ($w * .7), (int) ($w * .5), (int) ($w * .05));
                $this->sprite($im, (int) ($w * .4), (int) ($w * .5), (int) ($w * .64), 0, fn ($l, $s) => $this->tablet($l, $s, $hue));
                $this->sprite($im, (int) ($w * .62), (int) ($w * .6), (int) ($w * .5), 0, fn ($l, $s) => $this->tablet($l, $s, ($hue + 30) % 360));
                break;
            default:
                $this->box($im, $w, $w, $hue);
        }

        return $this->down($im, 640, 640);
    }

    private function category(string $name): \GdImage
    {
        $S = self::S;
        $w = 400 * $S;
        $hue = $this->hue($name);
        $im = $this->canvas($w, $w);
        $this->gradient($im, $w, $w, $hue);
        imagefilledellipse($im, (int) ($w / 2), (int) ($w / 2), (int) ($w * .66), (int) ($w * .66), $this->col($im, [255, 255, 255], 36));
        $cx = (int) ($w / 2);
        $accent = $this->rgb($hue, .75, .82);

        switch ($this->kind($name)) {
            case 'bottle':
                $this->bottle($im, $w, $w, $hue);
                break;
            case 'capsule':
                $this->sprite($im, $cx, $cx, (int) ($w * .9), 38, fn ($l, $s) => $this->capsule($l, $s, $hue));
                break;
            case 'tablet':
                $this->sprite($im, $cx, $cx, (int) ($w * .66), 0, fn ($l, $s) => $this->tablet($l, $s, $hue));
                break;
            default:
                $this->cross($im, $cx, $cx, (int) ($w * .17), $this->col($im, $accent));
        }

        return $this->down($im, 400, 400);
    }

    private function banner(string $type): \GdImage
    {
        [$w, $h] = [1600, 600];
        $hue = ['hero' => 205, 'banner' => 8, 'cta' => 170][$type] ?? 200;
        $im = $this->canvas($w * 2, $h * 2);
        $W = $w * 2;
        $H = $h * 2;
        [$r1, $g1, $b1] = $this->rgb($hue, .6, .62);
        [$r2, $g2, $b2] = $this->rgb(($hue + 22) % 360, .62, .95);
        for ($x = 0; $x < $W; $x++) {
            $t = $x / $W;
            imageline($im, $x, 0, $x, $H, imagecolorallocate($im, (int) ($r1 + ($r2 - $r1) * $t), (int) ($g1 + ($g2 - $g1) * $t), (int) ($b1 + ($b2 - $b1) * $t)));
        }
        mt_srand($hue);
        for ($k = 0; $k < 7; $k++) {
            $d = mt_rand((int) ($H * .35), (int) ($H * 1.1));
            imagefilledellipse($im, mt_rand((int) ($W * .35), $W), mt_rand(0, $H), $d, $d, imagecolorallocatealpha($im, 255, 255, 255, mt_rand(110, 120)));
        }
        // floating pills and crosses on the right, so the left stays calm for the text
        foreach ([[.78, .32, 520, 28, 'c'], [.9, .7, 420, -35, 'c'], [.66, .72, 360, 0, 't'], [.93, .26, 280, 0, 't']] as [$fx, $fy, $size, $ang, $kind]) {
            $this->sprite($im, (int) ($W * $fx), (int) ($H * $fy), $size * 2, $ang, fn ($l, $s) => $kind === 'c' ? $this->capsule($l, $s, ($hue + 150) % 360) : $this->tablet($l, $s, $hue));
        }
        foreach ([[.7, .12, 46], [.86, .5, 34], [.97, .86, 40], [.6, .52, 28]] as [$fx, $fy, $r]) {
            $this->cross($im, (int) ($W * $fx), (int) ($H * $fy), $r * 2, imagecolorallocatealpha($im, 255, 255, 255, 80));
        }

        return $this->down($im, $w, $h);
    }
}

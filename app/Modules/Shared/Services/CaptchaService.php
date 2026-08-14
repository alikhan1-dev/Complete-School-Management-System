<?php

namespace App\Modules\Shared\Services;

use App\Modules\Settings\Models\CaptchaSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

/**
 * CI Captchalib + captcha_model (session word + PNG under backend/captcha_images/).
 */
class CaptchaService
{
    public const SESSION_KEY = 'captchaCode';

    public function isEnabled(string $page): bool
    {
        $row = CaptchaSetting::query()->where('name', $page)->first();

        return $row !== null && (int) $row->status === 1;
    }

    /**
     * @return list<array{name: string, status: int}>
     */
    public function listSettings(): array
    {
        return CaptchaSetting::query()
            ->orderBy('id')
            ->get(['name', 'status'])
            ->map(fn (CaptchaSetting $row) => [
                'name' => (string) $row->name,
                'status' => (int) $row->status,
            ])
            ->all();
    }

    public function updateStatus(string $name, int $status): void
    {
        CaptchaSetting::query()->where('name', $name)->update([
            'status' => $status === 1 ? 1 : 0,
        ]);
    }

    /**
     * CI Captchalib::generate_captcha — sets session captchaCode and returns img HTML.
     *
     * @return array{word: string, image: string, filename: string}
     */
    public function generate(): array
    {
        $word = $this->randomWord();
        Session::forget(self::SESSION_KEY);
        Session::put(self::SESSION_KEY, $word);

        $dir = public_path('backend/captcha_images');
        File::ensureDirectoryExists($dir);
        $filename = time().'-'.uniqid((string) random_int(1000, 9999), false).'.png';
        $this->writeImage($dir.DIRECTORY_SEPARATOR.$filename, $word);

        return [
            'word' => $word,
            'filename' => $filename,
            'image' => '<img src="'.asset('backend/captcha_images/'.$filename).'" alt="captcha" />',
        ];
    }

    public function matches(string $posted): bool
    {
        $expected = (string) Session::get(self::SESSION_KEY, '');
        if ($expected === '') {
            return false;
        }

        return $posted === $expected;
    }

    /**
     * @return array<string, string>
     */
    public function validatePosted(string $page, string $posted): array
    {
        if (! $this->isEnabled($page)) {
            return [];
        }
        if (trim($posted) === '') {
            return ['captcha' => 'The Captcha field is required.'];
        }
        if (! $this->matches($posted)) {
            return ['captcha' => 'Incorrect Captcha'];
        }

        return [];
    }

    protected function randomWord(): string
    {
        $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $word = '';
        for ($i = 0; $i < 6; $i++) {
            $word .= $pool[random_int(0, strlen($pool) - 1)];
        }

        return $word;
    }

    protected function writeImage(string $path, string $word): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            File::put($path, $word);

            return;
        }
        $im = imagecreatetruecolor(150, 50);
        $bg = imagecolorallocate($im, 143, 210, 153);
        $fg = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, 150, 50, $bg);
        imagestring($im, 5, 18, 16, $word, $fg);
        imagepng($im, $path);
        imagedestroy($im);
    }
}

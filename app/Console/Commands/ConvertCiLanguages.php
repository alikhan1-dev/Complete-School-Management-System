<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ConvertCiLanguages extends Command
{
    protected $signature = 'school:convert-languages';

    protected $description = 'Convert CodeIgniter language files into Laravel lang/{locale}/system.php';

    public function handle(): int
    {
        $script = base_path('../tools/convert_languages/convert.php');
        if (! is_file($script)) {
            $this->error('Converter script not found.');

            return self::FAILURE;
        }

        passthru('php '.escapeshellarg($script), $code);
        $this->info('Done.');

        return $code === 0 ? self::SUCCESS : self::FAILURE;
    }
}

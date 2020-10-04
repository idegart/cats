<?php

namespace App\Console\Commands\TG;

use App\Models\Cat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Objects\PhotoSize;

class UploadCats extends Command
{
    protected $signature = 'tg:upload-cats';

    protected $description = 'Command description';

    /** @var Api */
    private Api $api;

    public function __construct(Api $api)
    {
        parent::__construct();

        $this->api = $api;
    }

    public function handle(): int
    {
        foreach (Storage::directories('cats') as $catDirectory) {

            $this->output->title('Start ' . $catDirectory);

            $catFiles = array_filter(Storage::files($catDirectory), static function ($catFile) {
                return Str::endsWith($catFile, 'jpg');
            });

            $this->progress = $this->output->createProgressBar(count($catFiles));

            foreach ($catFiles as $catFile) {
                $this->setCat($catFile);
                $this->progress->advance();
            }

            $this->progress->finish();
            $this->progress->clear();

            $this->output->success('Completed');
        }

        $this->output->success('Finished');

        return self::SUCCESS;
    }

    private function setCat(string $catFile): void
    {
        if (!Cat::query()->where('basename', '=', $basename = File::basename($catFile))->first()) {
            Cat::unguarded(function () use ($basename, $catFile) {

                $file = $this->uploadFile($catFile)->sortByDesc('file_size')->first();

                Cat::query()->create([
                    'basename' => $basename,
                    'file' => $catFile,
                    'tg_file_id' => $file['file_id'],
                    'tg_file_unique_id' => $file['file_unique_id'],
                ]);
            });
        }
    }

    private function uploadFile(string $catFile): PhotoSize
    {
        return retry(3, function () use ($catFile) {
            $response = $this->api->sendPhoto([
                'chat_id' => env('TELEGRAM_UPLOAD_USER'),
                'photo' => new InputFile(Storage::path($catFile)),
                'disable_notification' => true,
            ]);

            $this->api->deleteMessage([
                'chat_id' => $response->chat->id,
                'message_id' => $response->messageId
            ]);

            return $response->photo;
        }, 1000);
    }
}

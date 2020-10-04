<?php

namespace App\Console\Commands\TG;

use Illuminate\Console\Command;
use Telegram\Bot\Api;

class Setup extends Command
{
    protected $signature = 'tg:setup';

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
        $this->api->removeWebhook();

        $this->api->setWebhook([
            'url' => config('telegram.bots.mybot.webhook_url'),
        ]);

        return self::SUCCESS;
    }
}

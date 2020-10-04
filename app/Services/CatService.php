<?php

namespace App\Services;

use App\Models\Cat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Log;
use RuntimeException;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Update;
use Throwable;

class CatService
{
    private const REACT_LIKE = 'REACT_LIKE';
    private const REACT_DISLIKE = 'REACT_DISLIKE';

    private Api $api;

    private Update $update;

    private User $user;

    public function __construct(Api $api)
    {
        $this->api = $api;

        $this->update = $this->api->getWebhookUpdate();
    }

    public function reply(): Response
    {
        try {

            $this->initUser();

            if ($this->update->callbackQuery) {
                switch ($this->update->callbackQuery->data) {
                    case self::REACT_LIKE:
                        $this->catReact(true);
                        break;
                    case self::REACT_DISLIKE:
                        $this->catReact(false);
                        break;
                }
            }

            $this->sendRandomCat();

        } catch (Throwable $exception) {

            Log::error($exception);

            $this->api->sendMessage([
                'chat_id' => $this->update->getChat()->id,
                'text' => 'ERROR: ' . $exception->getMessage(),
            ]);

        } finally {
            return response('ok');
        }
    }

    protected function catReact(bool $isLiked): void
    {
        $this->removeCallbackButtons();

        $fileUniqueIds = collect($this->update->callbackQuery->message->photo)->pluck('file_unique_id');

        if (!count($fileUniqueIds)) {
            throw new RuntimeException('No photos in react');
        }

        $cat = Cat::query()->whereIn('tg_file_unique_id', $fileUniqueIds)->first();

        if (!$cat) {
            throw new RuntimeException('No cat found by file');
        }

        $this->user->cats()->updateExistingPivot($cat, ['is_liked' => $isLiked, 'updated_at' => now()]);
    }

    protected function removeCallbackButtons(): void
    {
        $this->api->editMessageReplyMarkup([
            'chat_id' => $this->update->getChat()->id,
            'message_id' => $this->update->getMessage()->messageId,
        ]);
    }

    protected function initUser(): void
    {
        $tgUser = $this->update->callbackQuery->from ?? $this->update->getMessage()->from;

        User::unguarded(function () use ($tgUser) {
            /** @var User $user */
            $user = User::query()->updateOrCreate([
                'tg_id' => $tgUser->id,
            ], [
                'username' => $tgUser->username,
                'first_name' => $tgUser->firstName,
                'language_code' => $tgUser->languageCode,
            ]);

            $this->user = $user;
        });
    }

    protected function sendRandomCat(): void
    {
        if (!($cat = $this->getRandomCat())) {
            $this->api->sendMessage([
                'chat_id' => $this->update->getChat()->id,
                'text' => 'No cats left',
            ]);
            return;
        }

        $this->api->sendPhoto([
            'chat_id' => $this->update->getChat()->id,
            'photo' => $cat->tg_file_id,
            'reply_markup' => (new Keyboard)
                ->inline()
                ->row(
                    Keyboard::inlineButton(['text' => '🙄️', 'callback_data' => self::REACT_DISLIKE]),
                    Keyboard::inlineButton(['text' => '️😍', 'callback_data' => self::REACT_LIKE]),
                )
        ]);

        $this->user->cats()->save($cat, ['created_at' => now()]);
    }

    protected function getRandomCat(): ?Model
    {
        return Cat::query()
            ->addSelect('cats.*')
            ->whereDoesntHave('users', function (Builder $query) {
                return $query->where('user_id', $this->user->getKey());
            })
            ->withCount([
                'users' => function (Builder $query) {
                    return $query->where('is_liked', true);
                },
            ])
            ->orderByDesc('users_count')
            ->inRandomOrder()
            ->first();
    }
}
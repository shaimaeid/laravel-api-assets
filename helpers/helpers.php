<?php

use App\Models\User;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

if (!function_exists('is_user')) {

    function is_user(): bool
    {
        $authUser = auth()->user();
        return !$authUser->isAdmin && !$authUser->isSales;
    }
}

if (!function_exists('is_admin_or_owner')) {

    function is_admin_or_owner($id): bool
    {
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();

        return $authUser->isAdmin || $authUser->id == $id;
    }
}

if (!function_exists('isProduction')) {
    function isProduction(): bool
    {
        return GetEnvNumber() == 3;
        // return true;
    }
}

if (!function_exists('log_error')) {

    function log_fb_error(Response $response, string $error_message = '', int $error_code = 000, Model $model = null): array
    {
        $obj_response = json_decode($response->body());

        Log::alert($error_message . $response->body());

        if ($model) {
            $publishStateService = new PublishStateService();
            $data = [
                'status' => array_flip(PublishState::STATUS)['FAILED'],
                'fail_message' => $response->body(),
                'user_id' => $model->user_id,
            ];
            $publishStateService->createPublishState(model: $model, data: $data);
        }

        $exception = [
            'message' => 'Application error',
            'internal_error_code' => $error_code,
            'fb_response' => $obj_response
        ];

        return $exception;
    }
}


if (!function_exists('GetEnvNumber')) {
    function GetEnvNumber()
    {
        switch (config('app.env')) {
            case 'local':
                $env_number = 1;
                break;
            case 'staging':
                $env_number = 2;
                break;
            case 'production':
                $env_number = 3;
                break;
            default:
                $env_number = 1;
                break;
        }
        return $env_number;
    }
}


if (!function_exists('getFBNextPageData')) {
    function getFBNextPageData($response): array
    {
        $data = [];

        if (isset($response['paging']['next'])) {
            while (isset($response['paging']['next'])) {
                $response = Http::get($response['paging']['next'])->json();

                if (isset($response['data'])) {
                    $resources = $response['data'];

                    if (count($resources)) {
                        $data = array_merge($data, $resources);
                    }
                } else if (isset($response['error'])) {
                    Log::alert('[***] getFBNextPageData');
                    Log::alert($response['error']);
                }
            }
        }

        return $data;
    }
}


if (!function_exists('maskLeadData')) {
    function maskLeadData($data, int $chars = 3)
    {
        // methods I tried and didn't solve the arabic numerals problems
        //$data = iconv('UTF-8', 'UTF-8//IGNORE', $data);
        //$data = preg_replace('/[^\x{80}-\x{7ff}]/u', '', $data); // remove non-UTF8 characters
        //$data = iconv('UTF-8', 'UTF-8//TRANSLIT//IGNORE', $data); // convert to UTF-8 and transliterate or ignore invalid characters

        // translate the arabic numeral into english
        $lookup = array(
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9'
        );
        $pattern = '/\p{Arabic}/u';

        $data = mb_convert_encoding($data, "UTF-8", "auto");

        $data = preg_replace_callback($pattern, function ($match) use ($lookup) {
            return $lookup[$match[0]];
        }, $data);

        // info("data:" . $data . " len:" . strlen($data));
        if (strlen($data) > 3) {
            $len = strlen($data) ?? 0;
            $masked_data = str_repeat('*', $len - $chars) . substr($data, -$chars);
            // info("masked:" . $masked_data);
            return $masked_data;
        }

        return '***';
    }
}

if (!function_exists('maskLeadEmail')) {
    function maskLeadEmail(string $data, int $chars = 3)
    {
        $data = mb_convert_encoding($data, "UTF-8", "auto");
        return substr($data, 0, $chars) . '****' . substr($data, strpos($data, "@"));
    }
}



if (!function_exists('isAccountManagerFor')) {
    function isAccountManagerFor(int $user_id): bool
    {
        // TODO: Enhancement -> get user and check account_manager_id
        /** @var \App\Models\User $user */
        $user = auth()->user();
        return $user->managed_accounts()->where('id', $user_id)->exists();
    }
}


if (!function_exists('getTimezone')) {
    function getTimezone(string|null $year = null, string|null $date = null)
    {
        if (!config('adsmanger.dst.is_active')) {
            return 'Africa/Cairo';
        }

        $year = $year ?? now()->format('Y');

        $startDSTMonth = config('adsmanger.dst.start_month');
        $startDSTDay = config('adsmanger.dst.start_day');
        $startDate = Carbon::parse("$year-$startDSTMonth-$startDSTDay");

        $endDSTMonth = config('adsmanger.dst.end_month');
        $endDSTDay = config('adsmanger.dst.end_day');
        $endDate = Carbon::parse("$year-$endDSTMonth-$endDSTDay");

        $time = Carbon::parse($date) ?? now();

        if ($time->between($startDate, $endDate)) {
            return 'Europe/Moscow';
        }

        return 'Africa/Cairo';
    }
}


if (!function_exists('diffBetweenDurations')) {
    function diffBetweenDurations(?Carbon $start, ?Carbon $end): int
    {
        if (!isset($start)) {
            throw new ErrorException("Start date is empty");
        }

        if (!isset($end)) {
            throw new ErrorException("End date is empty");
        }

        return $start->startOfDay()->diffInDays($end, false) + 1;
    }
}

if (!function_exists('toSubunit')) {
    function toSubunit($amount): int
    {
        return $amount * 100;
    }
}

if (!function_exists('fromSubunit')) {
    function fromSubunit($amount): int
    {
        return $amount / 100;
    }
}


if (!function_exists('isSubscriber')) {
    function isSubscriber(SponsorshipProgram $program, User $user): bool
    {
        return $program->subscriptions->contains('subscriber_id', $user->id);
    }
}

if (!function_exists('getCreatorAndCreatedFor')) {
    function getCreatorAndCreatedFor(?int $user_id): Collection
    {
        return collect([
            'creator'     => auth()->user() ?? User::findOrFail($user_id),
            'created_for' => $user_id === auth()->id() ?
                auth()->user() :
                User::findOrFail($user_id),
        ]);
    }
}



if (!function_exists('getSettingValue')) {
    function getSettingValue(string $name, ?string $type = null, mixed $content = null): mixed
    {
        $value = Setting::firstOrCreate(
            ['name'  => $name],
            ['value' => ['type' => $type, 'content' => $content]]
        )->content;

        if (isset($value)) {
            return $value;
        }

        throw new ErrorException("Setting with name $name is not found or its value is null");
    }
}

if (!function_exists('updateSettingValue')) {
    function updateSettingValue(string $name, mixed $value): void
    {
        DB::table('settings')
            ->where('name', $name)
            ->update(['value->content' => $value]);
    }
}

if (!function_exists('isMaintenance')) {
    function isMaintenance(): bool
    {
        return getSettingValue('maintenance_mode', 'boolean', false);
    }
}

if (!function_exists('convertCurrency')) {
    function convertCurrency(float|int $amount, Currency $fromCurrency, Currency $toCurrency)
    {
        if ($fromCurrency == Currency::EGP) {
            $code            = $toCurrency->code();
            $exchangeRate    = getSettingValue("{$code}_exchange_rate", 'double', config("adsmanger.{$code}.fx_rate"));
            $convertedAmount = $amount * $exchangeRate;
        } else {
            $code            = $fromCurrency->code();
            $exchangeRate    = getSettingValue("{$code}_exchange_rate", 'double', config("adsmanger.{$code}.fx_rate"));
            $convertedAmount = $amount / $exchangeRate;
        }

        $convertedAmount = round($convertedAmount, 2);

        return ['amount' => $convertedAmount, 'fx_rate' => $exchangeRate];
    }
}


if (!function_exists('isSales')) {
    function isSales(): bool
    {
        return auth()->user()->isSales;
    }
}


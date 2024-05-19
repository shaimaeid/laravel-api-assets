<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AdAccount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdAccountsApiController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('ad_account_access') || !auth()->user()->isAdmin, 403, 'You are not authorized.');

        $q              = $request->input('query', '');
        $is_paginated   = $request->input('is_paginated') === 'true';
        $rows           = (int) $request->input('rows', 10);

        $ad_accounts = AdAccount::when($q, function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                ->orWhere('fb_account_id', 'like', "%{$q}%")
                ->orWhere('balance', 'like', "%{$q}%")
                ->orWhere('threshold', 'like', "%{$q}%");
        })
            ->when($is_paginated, fn ($query) => $query->paginate($rows), fn ($query) => $query->take($rows)->get());

        return $is_paginated ?
            response()->json(new AdAccountCollection($ad_accounts)) :
            response()->json(['data' => AdAccountResource::collection($ad_accounts)]);
    }

    public function show(int $id)
    {
        abort_if(Gate::denies('ad_account_show'), 403, 'You are not authorized.');

        if (is_user()) {
            $ad_account = AdAccount::owner()->findOrFail($id);
        } else {
            $ad_account = AdAccount::findOrFail($id);
        }

        return Response::json(['data' => $ad_account], 200);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!auth()->user()->isAdmin, 403, 'You are not authorized.');

        $data = $request->validate([
            'threshold' => 'numeric|min:1',
            'status'    => 'sometimes|boolean',
        ]);

        $ad_account = AdAccount::findOrFail($id);
        $ad_account->update($data);

        return response()->json(['message' => 'Ad Account updated successfully']);
    }

    public function destroy(int $id)
    {
        abort_if(Gate::denies('ad_account_delete'), 403, 'You are not authorized.');

        if (is_user()) {
            $ad_account = AdAccount::owner()->findOrFail($id);
        } else {
            $ad_account = AdAccount::findOrFail($id);
        }

        $ad_account->delete();

        return Response::json(['message' => "Ad Account Deleted Successfully"], 200);
    }

    public function massDelete(Request $request)
    {
        abort_if(Gate::denies('ad_account_mass_delete'), 403, 'You are not authorized.');

        if (is_user()) {
            AdAccount::owner()->whereIn('id', $request->ids)->delete();
        } else {
            AdAccount::whereIn('id', $request->ids)->delete();
        }

        return Response::json(['message' => "All Selected Ad Accounts Deleted Successfully"], 200);
    }

    /**
     * Refresh Ad Account Status
     * @return array
     */
    public function refreshAccountsStatus()
    {
        if (!isProduction()) {
            return [
                'code' => 200,
                'message' => 'Successfully refreshed AdAccounts',
            ];
        }
        $token = config('adsmanger.facebook.token');

        $fb_res = Http::facebook()->get('me/adaccounts', [
            'fields' => 'id,name,account_status,disable_reason,balance',
            'access_token' => $token
        ]);

        if ($fb_res->successful()) {
            $fb_res = $fb_res->json();
            $data = isset($fb_res['data']) ? $fb_res['data'] : [];

            foreach ($data as $fb_ad_account) {
                $ad_account = AdAccount::where('fb_account_id', $fb_ad_account['id'])->first();
                if ($ad_account) {
                    $ad_account->update([
                        'name' => $fb_ad_account['name'],
                        'account_status' => $fb_ad_account['account_status'],
                        'disable_reason' => $fb_ad_account['disable_reason'],
                        'balance' => $fb_ad_account['balance'] / 100,
                        'status' => $fb_ad_account['account_status'] == array_flip(AdAccount::STATUS)['ACTIVE'] ? true : false,
                    ]);
                } else {
                    Log::alert('[***] refreshAccountsStatus@AdAccountsApiController');
                    Log::alert('[-] FB AdAccount: ' . $fb_ad_account['id'] . ', Not in our DB');

                    // return [
                    //     'code' => 404,
                    //     'message' => 'AdAccount not found in our DB',
                    // ];
                }
            }

            Log::info('[+] Successfully refreshed AdAccounts');
            return [
                'code' => 200,
                'message' => 'Successfully refreshed AdAccounts',
            ];
        } else {
            Log::error('[***] refreshAccountsStatus@AdAccountsApiController');
            Log::error('[-] Failed to refresh AdAccount');
            Log::error($fb_res);

            return [
                'code' => $fb_res->status(),
                'message' => 'Failed to refresh AdAccount',
            ];
        }
    }
}

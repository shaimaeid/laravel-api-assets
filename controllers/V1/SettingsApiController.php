<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Setting;
use App\Models\AdAccount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SettingsApiController extends Controller
{
    public function index()
    {
        return Setting::all();
    }

    public function update(UpdateSettingsRequest $request, Setting $setting)
    {
        $content = $request->validated()['value'];
        $this->updateSettingContent($setting, $content);

        return response()->json([
            'message' => 'Setting updated successfully',
            'data' => $setting
        ]);
    }

    public function getSettingByName(GetSettingByNameRequest $request)
    {
        $data = $request->validated();

        $name = data_get($data, 'name');
        if ($name) {
            $setting = Setting::where('name', $name)->firstOrFail();

            return response()->json([
                'name'      => $setting->name,
                'content'   => $setting->content,
            ]);
        }

        $names = data_get($data, 'names');
        if ($names) {
            $settings = Setting::whereIn('name', $names)->get()->map(fn ($setting) => ['name' => $setting->name, 'content' => $setting->content]);
            return response()->json([
                'data' => $settings,
            ]);
        }

        return response()->json([
            'message' => 'Something went wrong'
        ], 400);
    }


    private function updateSettingContent(Setting $setting, mixed $content)
    {
        $value              = $setting->value;
        $value['content']   = $content;
        $setting->update(['value' => $value]);
    }

    public function modelConst(ModelConstRequest $request)
    {
        $data = $request->validated();

        return response()->json([
            "data" => "App\\Models\\{$data['model']}"::getConstants()[$data['constant']]
        ]);
    }

    public function getAnnouncement()
    {
        if (true) {
            return response()->json(['message' => 'If you face any issue please call us on 01000024392']);
        }
    }

    public function changeAdAccountStatus(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|exists:ads_accounts,id',
            'status' => 'required|boolean',
        ]);

        AdAccount::findOrFail($data['account_id'])
            ->update([
                'status' => $data['status'],
            ]);

        return response()->json("Status updated successfully");
    }
  

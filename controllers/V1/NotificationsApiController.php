<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Notification;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class NotificationsApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies('notifications_access'), 403, 'You are not authorized.');

        $validated_data = $request->validate(
            [
                'filter'      => 'sometimes|array|min:1',
                'filter.type' => 'sometimes|string|in:menu,all'
            ]
        );

        $validated_data['filter'] = data_get($validated_data, 'filter.type', 'all');

        $user_id = Auth::id();

        if (is_user()) {
            if ($validated_data['filter'] == 'menu') {
                $notifications = Notification::owner()->where('read', 0)->orderBy('type')->orderBy('created_at', 'DESC')->paginate(50);
            } else {
                $notifications = Notification::owner()->orderBy('created_at', 'DESC')->paginate(50);
            }
        } else {
            if ($validated_data['filter'] == 'menu') {
                $notifications = Notification::where('user_id', $user_id)
                    ->orWhere('user_id', 1)
                    ->where('read', 0)
                    ->orderBy('type')
                    ->orderBy('created_at', 'DESC')
                    ->paginate(50);
            } else {
                $notifications = Notification::where('user_id', $user_id)
                    ->orWhere('user_id', 1)
                    ->orderBy('created_at', 'DESC')
                    ->paginate(50);
            }
        }

        return Response::json($notifications, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        abort_if(Gate::denies('notifications_create'), 403, 'You are not authorized.');

        $validated_data = $request->validate(
            [
                'user_id' => 'required|numeric|exists:users,id',
                'message_code' => 'required|string',
                'parameter_list' => 'required',
                'type'  => 'nullable|integer',
            ]
        );

        $notification = Notification::create($validated_data);

        return Response::json($notification, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        abort_if(Gate::denies('notifications_show'), 403, 'You are not authorized.');

        $notification = Notification::where('id', $id)->first();

        return Response::json($notification, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        abort_if(Gate::denies('notifications_edit'), 403, 'You are not authorized.');

        $validated_data = $request->validate(
            [
                'user_id' => 'required|numeric|exists:users,id',
                'message_code' => 'required|string',
                'parameter_list' => 'required',
                'read' => 'nullable|boolean',
                'type' => 'nullable|integer',
            ]
        );

        $notification = Notification::where('id', $id)->first()->update($validated_data);

        return Response::json($notification, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        abort_if(Gate::denies('notifications_delete'), 403, 'You are not authorized.');

        $notification = Notification::where('id', $id)->first();

        $notification->delete();

        return Response::json('Notification deleted successfully', 200);
    }

    public function readNotification($id)
    {
        abort_if(Gate::denies('notifications_read'), 403, 'You are not authorized.');

        $notification = Notification::where('id', $id)->update(['read' => 1]);

        return Response::json('Notification read successfully', 200);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetUserDataRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use App\Http\Requests\UsersRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserShowResource;
use App\Models\Permission;
use App\Services\UserService;
use ErrorException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsersApiController extends Controller
{

    public function __construct(private UserService $userService)
    {
        // 
    }

    public function index(UsersRequest $request)
    {
        abort_if(Gate::denies('user_access'), 403, 'You are not authorized.');

        $data           = $request->validated();
        $is_paginated   = ($data['is_paginated'] ?? 'true') === 'false' ? false : true;

        $users = User::when(
            auth()->user()->isStaff,
            fn ($q) => $q->excludeTestUsers()->whereNotIn('id', auth()->user()->managed_accounts?->pluck('id'))->where('is_staff', false)
        )
            ->withFilters($data);

        return $is_paginated ?
            response()->json(new UserCollection($users)) :
            response()->json(['data' => UserResource::collection($users)]);
    }

    public function store(StoreUserRequest $request)
    {
        $data                      = $request->validated();
        $data['password']          = bcrypt($request->password);
        $data['email_verified_at'] = now();

        $user = User::create($data);
        $user->roles()->sync($request->role_id);

        return Response::json([
            'data'    => $user,
            'message' => 'User Created Successfully'
        ], 201);
    }

    public function show(int $id)
    {
        abort_if(Gate::denies('user_show'), 403, 'You are not authorized.');

        if (is_admin_or_owner($id) || isAccountManagerFor($id)) {
            $user = User::with([
                'roles:id,title',
                'roles.permissions',
                'company'
            ])->findOrFail($id);
            return response()->json(['data' => new UserShowResource($user)], 200);
        }

        return response()->json(['message' => 'Unauthorized access'], 403);
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = User::when(
            auth()->user()->isAdmin || isAccountManagerFor($id),
            fn ($q) => $q->findOrFail($id),
            fn ($q) => $q->findOrFail(auth()->id())
        );

        $data = $request->validated();

        if (!empty($request->validated()['password'])) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        return Response::json([
            'message' => 'User Updated Successfully'
        ], 200);
    }

    public function destroy(int $id)
    {
        abort_if(Gate::denies('user_delete'), 403, 'You are not authorized.');

        $user = User::findOrFail($id);

        $user->delete();

        return Response::json(['message' => "User Deleted Successfully"], 200);
    }

    public function updateUserFbAccount(UpdateUserFbAccountRequest $request)
    {
        $params = $request->validated();

        if (isset($params['fb_id'])) {
            $this->validateUniqueFBProfile($params['fb_id']);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'fb_id'             => $params['fb_id'],
            'fb_name'           => $params['fb_name'],
            'fb_token'          => $params['fb_token'],
            'pages'             => data_get($params, 'pages'),
            'fb_connected_at'   => isset($params['fb_id']) ? now() : null,
        ]);

        return response()->json([
            'message'   => 'User Updated Successfully',
            'data'      => UserShowResource::make($user)
        ]);
    }

    public function validateUniqueFBProfile(?int $fbID): void
    {
        if (User::where('fb_id', $fbID)->exists()) {
            throw new ErrorException('This Facebook profile is already connected to another LeadsMart account');
        }
    }

    public function updateUserStatus(UpdateUserStatusRequest $request = null, $status = null, $id = null)
    {
        $user = User::findOrFail($id);

        $user->update(['status' => $request->status ?? $status]);

        return Response::json([
            'message' => 'User Status Updated Successfully',
            'data' => $user,
        ]);
    }

    public function updateUserRole(int $role_id, User $user): array
    {
        $user->roles()->sync($role_id);
        info('[+] User: ' . $user->name . ' role updated to ' . $role_id);

        return [
            'code' => 200,
            'message' => 'Role updated successfully'
        ];
    }

    public function updateUserCompany(int $company_id, User $user): array
    {
        $user->update(['company_id' => $company_id]);

        return [
            'code' => 200,
            'message' => 'Company updated successfully'
        ];
    }

    /**
     * ADMIN ONLY
     */
    public function updateUserData(UpdateUserDataRequest $request, User $user)
    {
        $user_role_res      = $request->whenFilled('role_id', fn ($role_id) => $this->updateUserRole($role_id, $user));
        $user_status_res    = $request->whenFilled('status', fn ($status) => $this->updateUserStatus(request: null, status: $status, id: $user->id));
        $user_company_res   = $request->whenFilled('company_id', fn ($company_id) => $this->updateUserCompany($company_id, $user));

        $request->whenFilled('is_staff', fn ($is_staff) => $user->update(['is_staff' => $is_staff]));


        return response()->json([
            'message' => 'User data updated successfully',
            'data' => $user
        ]);
    }

    public function getSuperAdmins()
    {
        if (auth()->user()->isAdmin) {
            $superAdminLevel = array_flip(Permission::LEVEL)['SUPERADMIN'];

            $users = User::whereHas('roles.permissions', function ($query) use ($superAdminLevel) {
                $query->where('level', $superAdminLevel);
            })->select('id', 'name')->get();

            return response()->json(['data' => $users, 'count' => count($users)], 200);
        }

        return response()->json([
            'message'   => 'Unauthorized Access',
        ], 403);
    }




    public function getUserData(GetUserDataRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'data' => $user->only(data_get($data, 'fields')),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\FacebookPage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetFacebookPagesRequest;
use App\Http\Requests\RequestAccessToPageRequest;
use App\Http\Resources\facebookPageCollection;
use App\Models\User;
use App\Services\BusinessAccountService;
use App\Services\FacebookPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FacebookPagesApiController extends Controller
{
    public function __construct(private FacebookPageService $facebookPageService)
    {
        // 
    }

    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'user_id'  => 'sometimes|exists:users,id',
            'owner_id' => 'sometimes|exists:users,id',
            'rows'     => 'sometimes|numeric|min:1',
        ]);

        $pages = FacebookPage::query();

        // get pages assigned to a user
        if (isset($validatedData["user_id"])) {
            $pages->whereHas('users', function ($query) use ($validatedData) {
                $query->where('users.id', $validatedData["user_id"]);
            });
        }

        // get owner pages
        if (isset($validatedData["owner_id"])) {
            $pages->with('users:id,name');
            $pages->where('owner_id', $validatedData["owner_id"]);
        }

        $results = $pages->get()->paginate(data_get($validatedData, 'rows', 100));
        return response()->json(new facebookPageCollection($results), 200);
    }

    public function store(Request $request)
    {
        //store owner id
        $validatedData = $request->validate([
            'name'       => 'required',
            'fb_page_id' => 'required',
            'img_url'    => 'required',
            'page_token' => 'required',
            'owner_id'   => 'sometimes|exists:users,id',
        ]);

        $recordExists = FacebookPage::query()
            ->where('fb_page_id', $validatedData['fb_page_id'])
            ->where('owner_id', $validatedData['owner_id'])
            ->exists();

        if ($recordExists) {
            return response()->json(['message' => 'Page already added'], 400);
        }

        $page = FacebookPage::create($validatedData);

        return response()->json($page, 201);
    }

    public function show(int $id)
    {
        $page = FacebookPage::find($id);

        $this->authorize('resource_access', ['resource_owner_id' => $page->owner_id, 'account_manager_access' => true]);

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Facebook Page not found',
            ], 404);
        }

        return response()->json($page);
    }

    public function update(Request $request,  int $id)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'fb_page_id' => 'required',
            'img_url' => 'required',
            'page_token' => 'required',
        ]);

        $page = FacebookPage::find($id);

        $this->authorize('resource_access', ['resource_owner_id' => $page->owner_id, 'account_manager_access' => true]);

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Facebook Page not found',
            ], 404);
        }

        $page->update($validatedData);
        return response()->json($page);
    }

    public function destroy(int $id)
    {
        $page = FacebookPage::find($id);

        $this->authorize('resource_access', ['resource_owner_id' => $page->owner_id, 'account_manager_access' => true]);

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Facebook Page not found',
            ], 404);
        }

        $users = $page->users()->get()->count();
        if ($users >= 1) {
            return response()->json([
                'status' => false,
                'message' => "Facebook Page has ({$users}) assigned users. unassign all users then retry.",
            ], 404);
        }

        $page->delete();
        return response()->json(null, 204);
    }

    public function assignUser(Request $request)
    {
        //TODO: validate fb page id not assigned to user before
        $page = FacebookPage::find($request->fb_page_id);

        if (!(Gate::allows('admin_or_sales') || Gate::allows('resource_access', ['resource_owner_id' => $page->owner_id]))) {
            return response()->json(['message' => 'Unauthorized Action'], 401);
        }

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Facebook Page not found',
            ], 404);
        }

        $users = $page->users()->pluck('users.id');
        $userIds = $request->input('user_ids', []);

        // already assigned
        $existingUserIds = collect($request->input('user_ids', []))->intersect($users);

        // new users to be assigned
        $nonExistingUserIds = collect($request->input('user_ids', []))->diff($users);

        if (count($nonExistingUserIds) >= 1) {
            $page->users()->syncWithoutDetaching($userIds);

            return response()->json([
                'message' => 'Users assigned successfully',
                'existing_user_ids' => $existingUserIds->toArray(),
                'non_existing_user_count' => $nonExistingUserIds,
            ]);
        }

        return response()->json([
            'message' => 'Users already assigned to this page.'
        ]);
    }

    public function unassignUser(Request $request)
    {
        $page = FacebookPage::find($request->fb_page_id);

        if (!(Gate::allows('admin_or_sales') || Gate::allows('resource_access', ['resource_owner_id' => $page->owner_id]))) {
            return response()->json(['message' => 'Unauthorized Action'], 401);
        }

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Facebook Page not found',
            ], 404);
        }

        $userIds = $request->input('user_ids', []);
        $detached = $page->users()->detach($userIds);

        if ($detached > 0) {
            return response()->json(['message' => 'Users unassigned successfully']);
        }

        return response()->json([
            'status' => false,
            'message' => 'User not assigned that page.',
        ], 404);
    }

    public function getAssignedUsers(Request $request)
    {
        $page = FacebookPage::find($request->fb_page_id);

        $this->authorize('resource_access', ['resource_owner_id' => $page->owner_id]);

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Facebook Page not found',
            ], 404);
        }

        $users = $page->users()->get();
        return response()->json($users);
    }

    public function requestAccessToPage(RequestAccessToPageRequest $request)
    {
        $data = $request->validated();

        $serviceResponse = (new BusinessAccountService(fbPageId: $data['fb_page_id']))->requestAccessToPage();

        return response()->json($serviceResponse);
    }

    public function getFacebookPages(User $user): JsonResponse
    {
        $this->authorize('resource_access', ['resource_owner_id' => $user->id, 'account_manager_access' => true]);

        $pages = $this->facebookPageService->getFacebookPages($user);

        return response()->json(['data' => $pages]);
    }
}

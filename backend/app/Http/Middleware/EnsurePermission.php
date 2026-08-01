<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'غير مصرح بالدخول'], 401);
        }

        foreach ($permissions as $permission) {
            if ($this->permissions->userCan($user, $permission)) {
                return $next($request);
            }
        }

        return response()->json(['success' => false, 'message' => 'لا تملك صلاحية تنفيذ هذا الإجراء'], 403);
    }
}

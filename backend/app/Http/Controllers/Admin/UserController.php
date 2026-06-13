<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * List users with optional search (q) and role filter.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->withCount('reservas')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q');
                $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->orderByDesc('created_at')
            ->get();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return (new UserResource($user->refresh()->loadCount('reservas')))->response()->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, User $usuario): UserResource
    {
        $data = $request->validated();

        // Don't overwrite password with an empty value.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Self-protection: an admin can't demote or deactivate their own account.
        if ($usuario->id === $request->user()->id) {
            if (array_key_exists('role', $data) && $data['role'] !== 'admin') {
                throw ValidationException::withMessages(['role' => ['No puedes cambiar tu propio rol.']]);
            }
            if (array_key_exists('activo', $data) && $data['activo'] === false) {
                throw ValidationException::withMessages(['activo' => ['No puedes desactivar tu propia cuenta.']]);
            }
        }

        $usuario->update($data);

        return new UserResource($usuario->loadCount('reservas'));
    }

    public function destroy(Request $request, User $usuario): JsonResponse
    {
        if ($usuario->id === $request->user()->id) {
            throw ValidationException::withMessages(['id' => ['No puedes eliminar tu propia cuenta.']]);
        }

        if ($usuario->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            throw ValidationException::withMessages(['id' => ['No puedes eliminar el único administrador.']]);
        }

        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado.']);
    }
}

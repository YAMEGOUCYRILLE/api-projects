<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(): JsonResponse
    {
        $users = User::with('trips')->paginate(10);
        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->load('trips');
        return response()->json($user);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if (auth('api')->id() !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only('name', 'email'));

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if (auth('api')->id() !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UiOptionsController extends Controller
{
    public function leads(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->when($search !== '', function ($query) use ($search) {
                $query
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        $results = $users->map(function (User $user) {
            return [
                'id'       => (string) $user->id,
                'text'     => $user->name,
                'subtitle' => $user->email,
                'image'    => $this->avatarFor($user->name),
            ];
        })->values();

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => false],
        ]);
    }

    private function avatarFor(string $name): string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=f5a623&color=111111&size=64';
    }
}


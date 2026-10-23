<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * UC-10 Mengelola Akun Pengguna.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->orderByDesc('status')
            ->orderBy('nama')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => User::ROLE_KASIR, 'status' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validated($request));

        return redirect()->route('admin.pengguna.index')->with('success', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && ($data['role'] !== User::ROLE_ADMIN || ! $data['status'])) {
            return back()->withInput()->with('error', 'Anda tidak dapat menonaktifkan atau mengubah peran akun sendiri.');
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.pengguna.index')->with('success', 'Akun pengguna diperbarui.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['status' => ! $user->status]);

        return back()->with('success', "Akun {$user->nama} ".($user->status ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100',
                Rule::unique('users', 'email')->ignore($user?->user_id, 'user_id')],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ]);

        $data['status'] = $request->boolean('status');

        return $data;
    }
}

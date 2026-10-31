<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Msg;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public const STATUSES = ['pending' => 'Menunggu verifikasi email', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

    public function index(Request $r)
    {
        $q = trim((string) $r->query('q', ''));
        $status = (string) $r->query('status', '');

        $users = User::when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', '%' . $q . '%')->orWhere('email', 'like', '%' . $q . '%')))
            ->when(array_key_exists($status, self::STATUSES), fn ($x) => $x->where('account_status', $status))
            ->orderByRaw("case account_status when 'pending' then 0 when 'rejected' then 1 else 2 end")
            ->orderByDesc('id')->paginate(15)->withQueryString();

        $counts = User::selectRaw('account_status, count(*) as c')->groupBy('account_status')->pluck('c', 'account_status');

        return view('admin.users.index', ['users' => $users, 'q' => $q, 'status' => $status, 'counts' => $counts, 'statuses' => self::STATUSES]);
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user, 'statuses' => self::STATUSES]);
    }

    public function update(Request $r, User $user)
    {
        Msg::wa($r, 'phone');
        $data = $r->validate([
            'name' => 'required|string|min:3|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'regex:' . Msg::WA_RULE],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'account_status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'status_note' => 'nullable|string|max:200',
            'password' => 'nullable|string|min:8|max:100',
        ], Msg::messages(), Msg::attributes() + ['status_note' => 'Catatan']);

        $self = (int) $user->id === (int) $r->user()->id;
        if ($self && ($data['role'] !== 'admin' || $data['account_status'] !== 'approved')) {
            return back()->withInput()->with('toast', 'Anda tidak dapat menurunkan peran atau mencabut persetujuan akun Anda sendiri.');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $data['email'] = strtolower($data['email']);

        $status = $data['account_status'];
        $note = $data['status_note'] ?? null;
        unset($data['account_status'], $data['status_note']);

        $user->update($data);
        $this->setStatus($user, $status, $r->user()->id, $note);

        return redirect()->route('admin.users.index')->with('toast', 'Pengguna diperbarui.');
    }

    public function approve(Request $r, User $user)
    {
        $this->setStatus($user, 'approved', $r->user()->id, null);
        Notify::to($user->id, 'account', 'Akun Anda disetujui', 'Selamat datang di Clover. Anda sudah dapat berbelanja dan memesan jasa.', route('account'));

        return back()->with('toast', 'Akun ' . $user->name . ' disetujui manual (verifikasi email dilewati). Pelanggan sudah dapat masuk.');
    }

    public function reject(Request $r, User $user)
    {
        if ((int) $user->id === (int) $r->user()->id) {
            return back()->with('toast', 'Anda tidak dapat menolak akun Anda sendiri.');
        }
        $d = $r->validate(['note' => 'nullable|string|max:200'], Msg::messages(), Msg::attributes());
        $this->setStatus($user, 'rejected', $r->user()->id, $d['note'] ?? null);

        return back()->with('toast', 'Pendaftaran ' . $user->name . ' ditolak.');
    }

    private function setStatus(User $user, string $status, int $adminId, ?string $note): void
    {
        $user->forceFill([
            'account_status' => $status,
            'status_note' => $status === 'approved' ? null : $note,
            'approved_at' => $status === 'approved' ? ($user->approved_at ?: now()) : null,
            'email_verified_at' => $status === 'approved' ? ($user->email_verified_at ?: now()) : $user->email_verified_at,
            'approved_by' => $status === 'approved' ? $adminId : null,
        ])->save();
    }

    public function destroy(Request $r, User $user)
    {
        if ((int) $user->id === (int) $r->user()->id) {
            return back()->with('toast', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }
        $user->delete();

        return redirect()->route('admin.users.index')->with('toast', 'Pengguna dihapus.');
    }
}

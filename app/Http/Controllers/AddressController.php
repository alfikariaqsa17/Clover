<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use App\Support\Msg;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    private function rules(): array
    {
        return [
            'label' => 'required|string|max:40',
            'recipient' => 'required|string|min:3|max:100',
            'phone' => ['required', 'regex:' . Msg::WA_RULE],
            'address' => 'required|string|min:5|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => ['required', 'regex:/^[0-9]{5}$/'],
        ];
    }

    private function mine(Request $r, UserAddress $address): UserAddress
    {
        abort_unless((int) $address->user_id === (int) $r->user()->id, 404);

        return $address;
    }

    public function index(Request $r)
    {
        return view('account.addresses', ['addresses' => $r->user()->addresses()->orderByDesc('is_default')->orderBy('id')->get()]);
    }

    public function store(Request $r)
    {
        Msg::wa($r, 'phone');
        $data = $r->validate($this->rules(), Msg::messages(), Msg::attributes());
        $user = $r->user();
        $makeDefault = $r->boolean('is_default') || ! $user->addresses()->exists();
        if ($makeDefault) {
            $user->addresses()->update(['is_default' => false]);
        }
        $user->addresses()->create($data + ['is_default' => $makeDefault]);

        return redirect()->route('account.addresses')->with('toast', 'Alamat disimpan.');
    }

    public function update(Request $r, UserAddress $address)
    {
        $this->mine($r, $address);
        Msg::wa($r, 'phone');
        $data = $r->validate($this->rules(), Msg::messages(), Msg::attributes());
        if ($r->boolean('is_default')) {
            $r->user()->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }
        $address->update($data);

        return redirect()->route('account.addresses')->with('toast', 'Alamat diperbarui.');
    }

    public function destroy(Request $r, UserAddress $address)
    {
        $this->mine($r, $address)->delete();
        $first = $r->user()->addresses()->orderBy('id')->first();
        if ($first && ! $r->user()->addresses()->where('is_default', true)->exists()) {
            $first->update(['is_default' => true]);
        }

        return redirect()->route('account.addresses')->with('toast', 'Alamat dihapus.');
    }
}

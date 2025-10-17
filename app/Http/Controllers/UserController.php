<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q');

        $users = \App\Models\User::with('rols')
            ->when($q, function ($query, $q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10)               // <— ¡importante!
            ->withQueryString();         // <— opcional (para conservar ?q=...)

        $rols = \App\Models\Rol::orderBy('nombre')->get();

        return view('users.index', compact('users', 'rols'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6'],
            'roles'    => ['array'],
            'roles.*'  => ['integer','exists:rols,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email'=> $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->rols()->sync($request->input('roles', []));

        return redirect()->route('users.index')->with('success','Usuario creado.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:6'],
            'roles'    => ['array'],
            'roles.*'  => ['integer','exists:rols,id'],
        ]);

        $user->name  = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $user->rols()->sync($request->input('roles', []));

        return redirect()->route('users.index')->with('success','Usuario actualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Evita que alguien se borre a sí mismo
        if (Auth::id() === $user->id) {                  // ← usa el Facade, sin paréntesis raros
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        // Quita roles y borra
        $user->rols()->detach();                         // ← tu relación "rols"
        $user->delete();

        return redirect()
            ->route('users.index')                       // ← sin "route:"
            ->with('success', 'Usuario eliminado correctamente.');  // ← sin "key:"/"value:"
    }


}

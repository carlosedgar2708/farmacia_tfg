<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q');

        $users = User::with('rols')
            ->when($q, function ($query, $q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $rols = Rol::orderBy('nombre')->get();

        return view('users.index', compact('users', 'rols'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['nullable', 'string', 'max:50', 'unique:users,username'], // lo hacemos opcional y lo generamos si no viene
            'name'     => ['required','string','max:255'],
            'apellido' => ['nullable','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'telefono' => ['nullable','string','max:30'],
            'activo'   => ['nullable', 'boolean'],
            'password' => ['required','string','min:6'],
            'roles'    => ['array'],
            'roles.*'  => ['integer','exists:rols,id'],
        ]);

        // Genera username si no vino: parte local del email -> slug
        $username = $data['username']
            ?? Str::slug(explode('@', $data['email'])[0]);

        // desambiguar si existiera
        if (User::where('username', $username)->exists()) {
            $username .= '-' . Str::lower(Str::random(4));
        }

        $user = User::create([
            'username' => $username,
            'name'     => $data['name'],
            'apellido' => $data['apellido'] ?? null,
            'email'    => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'activo'   => (bool)($data['activo'] ?? true),
            'password' => Hash::make($data['password']),
        ]);

        $user->rols()->sync($request->input('roles', []));

        return redirect()->route('users.index')->with('success','Usuario creado.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users','username')->ignore($user->id)],
            'name'     => ['required','string','max:255'],
            'apellido' => ['nullable','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'telefono' => ['nullable','string','max:30'],
            'activo'   => ['nullable','boolean'],
            'password' => ['nullable','string','min:6'],
            'roles'    => ['array'],
            'roles.*'  => ['integer','exists:rols,id'],
        ]);

        // si mandan username, úsalo; si no, deja el actual
        if (!empty($data['username'])) {
            $user->username = $data['username'];
        }

        $user->name     = $data['name'];
        $user->apellido = $data['apellido'] ?? null;
        $user->email    = $data['email'];
        $user->telefono = $data['telefono'] ?? null;
        if (array_key_exists('activo', $data)) {
            $user->activo = (bool)$data['activo'];
        }

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

<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('role')->orderby('id', 'desc')->get();
        return view('admin.pages.user.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::orderBy('name', 'asc')->get();
        return view('admin.pages.user.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required',
            'password' => 'required|min:3|max:15',
            'password_confirmation' => 'required|same:password',
            'role_id' => 'required'
        ]);

        // $user = User::create([
        //     'name'=>$request->name,
        //     'email'=>$request->email,
        //     'role_id'=>$request->role_id,
        //     'password'=>Hash::make($request->password),
        // ]);

        $user       = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        if($request->active){
            $user->active = 1;
        }else{
           $user->active =0; 
        }
        $user->role_id = $request->role_id;
        $user->password = Hash::make($request->password);

        // $user=false;

        // if($user){
        if ($user->save()) {
            return redirect()
                ->route('users.index')
                ->with('success', 'user created successfully');
        } else {
            return redirect()
                ->route('users.create')
                ->with('error', 'user not created');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with('role')->findOrFail($id);

        return view('admin.pages.user.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $roles = Role::all();
        $user = User::find($id);
        // dd($user);
        return view('admin.pages.user.edit', compact('roles','user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // dd($request->all());
        $request->validate([
            'name'=>'required|min:3|max:100',
            'email'=>"required|email|unique:users,email,$id",
            'role_id'=>'required',
            'phone'=>'required'
        ]);

        
        $user        = User::find($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->role_id = $request->role_id;
        $user->phone = $request->phone;
        $user->save();


        if($user->save()){
            return redirect()
            ->route('users.index')
            ->with('success','user updated successfully');

        }else{
            return redirect()
            ->route('users.create')
            ->with('error','user not updated');
        }




    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

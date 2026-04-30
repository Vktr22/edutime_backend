<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;


class TeacherController extends Controller
{
    public function index()
    {
        return User::where('role', 'teacher')
            ->select('id', 'name', 'email')
            ->get();
    }

    public function show($id)
    {
        return User::where('role', 'teacher')
            ->select('id', 'name', 'email')
            ->findOrFail($id);
    }
}

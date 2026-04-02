<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /*
        nem számit alabbol a metodusok sorrendje, de:
        Helper metódusokat (mint az isTeacher/isStudent) általában a class vége felé szokás tenni.
        A settings/properties (fillable, hidden, casts) maradjanak feljebb.
        Ha role-t tömegesen akarod menteni (create/update), akkor érdemes a role mezőt hozzáadni a fillable tömbhöz is.
     */
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    
    /*
        a role mezo is fillable lett mert:
        mikor pl tinkerbe v seederrel uj rekord felvitelenel teacher-t allitottam be, atirta studentre,
        mart ugye az az alap beallitott ertek!!!!
     */

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }
}

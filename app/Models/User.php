<?php

namespace App\Models;

use App\Models\Customer;
use Laravel\Sanctum\HasApiTokens;
use App\Models\TransactionPinResetToken;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
    protected $appends = ['name'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function transaction_pin_token()
    {
        return $this->hasMany(TransactionPinResetToken::class);
    }

    function admin()
    {
        return $this->hasOne(Admin::class, 'user_id', 'id');
    }

    public function getNameAttribute()
    {
        return $this->firstname . " " . $this->middlename . " " . $this->lastname;
    }

    public function downlines()
    {
        return $this->hasMany(User::class, 'referral', 'username');
    }
}

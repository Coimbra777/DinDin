<?php

namespace App\Models;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'password',
        'username',
        'group_id',
        'image',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /*
     * Get the group that the user belongs to
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function financeCategories()
    {
        return $this->hasMany(Category::class);
    }

    public function financeTransactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

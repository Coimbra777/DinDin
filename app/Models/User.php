<?php

namespace App\Models;

use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'group_id',
        'image'
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

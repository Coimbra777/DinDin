<?php

namespace App\Models;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'onboarding_completed',
        'is_admin',
    ];

    protected $casts = [
        'onboarding_completed' => 'boolean',
        'is_admin' => 'boolean',
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

    /**
     * Módulos SaaS atribuídos explicitamente a este utilizador (pivot saas_module_user).
     */
    public function saasModules(): BelongsToMany
    {
        return $this->belongsToMany(SaasModule::class, 'saas_module_user')
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Acesso a módulo SaaS: administradores — tudo; slug {@code finance} — qualquer utilizador
     * (entrada na app Finanças); restantes — só se estiverem na pivot {@see saasModules()}.
     */
    public function canAccessSaasModule(string $slug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($slug === 'finance') {
            return true;
        }

        return $this->saasModules()->where('slug', $slug)->exists();
    }
}

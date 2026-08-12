<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'email', 'password', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'active' => 'boolean',
        ];
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Determine if the user has a specific role.
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    /**
     * Determine if the user has any of the specified roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->pluck('slug')->intersect($roles)->isNotEmpty();
    }

    /**
     * Assign a role or roles to the user.
     */
    public function assignRole(string|array|Role $role): void
    {
        if ($role instanceof Role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
            return;
        }

        if (is_string($role)) {
            $roleModel = Role::where('slug', $role)->first();
            if ($roleModel) {
                $this->roles()->syncWithoutDetaching([$roleModel->id]);
            }
            return;
        }

        if (is_array($role)) {
            $roleIds = [];
            foreach ($role as $r) {
                if ($r instanceof Role) {
                    $roleIds[] = $r->id;
                } elseif (is_string($r)) {
                    $roleModel = Role::where('slug', $r)->first();
                    if ($roleModel) {
                        $roleIds[] = $roleModel->id;
                    }
                }
            }
            if (!empty($roleIds)) {
                $this->roles()->syncWithoutDetaching($roleIds);
            }
        }
    }

    /**
     * Determine if the user is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isActive() && $this->hasRole('admin');
    }
}

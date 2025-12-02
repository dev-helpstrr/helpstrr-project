<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ServiceCategory extends Model
{
    protected $table = 'service_categories';
    protected $fillable = [
        'role_id','parent_id','name','slug','is_active','sort_order',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    public function role(): BelongsTo { return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id'); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order'); }
    public function scopeRoot($query) { return $query->whereNull('parent_id')->orderBy('sort_order'); }
    public function scopeForRole($query, $roleId) { return $query->where('role_id', $roleId); }
}

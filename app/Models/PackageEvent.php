<?php

namespace App\Models;

use App\Enums\PackageEventType;
use Database\Factories\PackageEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['package_id', 'user_id', 'event', 'metadata'])]
class PackageEvent extends Model
{
    /** @use HasFactory<PackageEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'event' => PackageEventType::class,
            'metadata' => 'array',
        ];
    }
}

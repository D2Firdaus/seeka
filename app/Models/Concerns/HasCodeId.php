<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * PK berupa kode string berurutan, mis. CLM001, CLM002, ...
 *
 * Pemakaian di model:
 *   use HasCodeId;
 *   protected $primaryKey = 'claim_id';
 *   protected string $codePrefix = 'CLM';
 *
 * Simpan di dalam transaksi supaya penguncian baris berlaku:
 *   DB::transaction(fn () => Claim::create([...]));
 */
trait HasCodeId
{
    public static function bootHasCodeId(): void
    {
        static::creating(function ($model) {
            if (empty($model->getKey())) {
                $model->setAttribute($model->getKeyName(), static::generateCode($model));
            }
        });
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    protected static function generateCode($model): string
    {
        $prefix = $model->codePrefix;
        $key = $model->getKeyName();
        $start = strlen($prefix) + 1;

        $last = DB::table($model->getTable())
            ->where($key, 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByRaw("CAST(SUBSTRING($key, $start) AS UNSIGNED) DESC")
            ->value($key);

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}

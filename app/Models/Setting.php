<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    private const CACHE_KEY = 'settings.all';

    /**
     * All settings as a key => casted-value map, cached forever and
     * busted on every set(). This is the single read path used by
     * Setting::get().
     */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->get()
                ->mapWithKeys(fn (Setting $s) => [$s->key => $s->cast()])
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $map = static::map();

        return array_key_exists($key, $map) ? $map[$key] : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $setting = static::query()->firstOrNew(['key' => $key]);
        $setting->value = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        $setting->save();

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Settings in one group as models, in insertion order — for the form.
     *
     * @return Collection<int, Setting>
     */
    public static function group(string $group): Collection
    {
        return static::query()->where('group', $group)->orderBy('id')->get();
    }

    /** Casted value of this row, per its `type`. */
    public function cast(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->type) {
            'int' => (int) $this->value,
            'float' => (float) $this->value,
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            default => $this->value,
        };
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

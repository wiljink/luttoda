<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    /** Human labels for the group headers, in display order. */
    private const GROUP_LABELS = [
        'fuel' => 'Fuel & Diesel',
        'dues' => 'Daily Dues',
        'benefits' => 'Member Benefits',
        'eligibility' => 'Benefit Eligibility Thresholds',
        'loans' => 'Loans',
        'general' => 'General',
    ];

    public function index()
    {
        $groups = Setting::query()
            ->orderBy('id')
            ->get()
            ->groupBy('group')
            ->sortKeys();

        return view('settings.index', [
            'groups' => $groups,
            'groupLabels' => self::GROUP_LABELS,
        ]);
    }

    public function update(Request $request)
    {
        $settings = Setting::query()->get()->keyBy('key');

        // Only keys actually present in the payload are validated and
        // saved, so a partial form still works. bool keys are always
        // considered present (an unchecked box submits nothing).
        $input = (array) $request->input('settings', []);

        $rules = [];
        foreach ($settings as $key => $setting) {
            if ($setting->type !== 'bool' && ! array_key_exists($key, $input)) {
                continue;
            }
            $rules["settings.$key"] = match ($setting->type) {
                'int' => ['required', 'integer', 'min:0'],
                'float' => ['required', 'numeric', 'min:0'],
                'bool' => ['nullable', 'boolean'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        $request->validate($rules);

        foreach ($settings as $key => $setting) {
            if ($setting->type === 'bool') {
                Setting::set($key, $request->boolean("settings.$key"));
            } elseif (array_key_exists($key, $input)) {
                Setting::set($key, $input[$key]);
            }
        }

        return redirect()->route('settings.index')
            ->with('success', 'Settings saved.');
    }

    /** Kept for reuse by views/labels if a key has no stored label. */
    public static function humanize(string $key): string
    {
        return Str::of($key)->replace('_', ' ')->title();
    }
}

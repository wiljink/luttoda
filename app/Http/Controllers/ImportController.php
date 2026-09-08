<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Services\Import\DailyCollectionImporter;
use App\Services\Import\ExpensesImporter;
use App\Services\Import\MembersImporter;
use App\Services\Import\RentalImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImportController extends Controller
{
    private const TEMPLATES = [
        'members' => MembersImporter::class,
        'daily_collection' => DailyCollectionImporter::class,
        'expenses' => ExpensesImporter::class,
        'rental' => RentalImporter::class,
    ];

    public function index()
    {
        return view('settings.import', [
            'logs' => ImportLog::with('user')->latest()->limit(20)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'template' => 'required|in:'.implode(',', array_keys(self::TEMPLATES)),
            'file' => 'required|file|mimes:xlsx|max:5120',
            'preview' => 'nullable|boolean',
        ]);

        $preview = $request->boolean('preview');
        $originalName = $request->file('file')->getClientOriginalName();
        $path = $request->file('file')->store('imports');

        $importer = app(self::TEMPLATES[$data['template']]);
        if (method_exists($importer, 'forUser')) {
            $importer->forUser(auth()->id());
        }

        try {
            $log = $importer->run(Storage::path($path), $preview, auth()->id(), $originalName);
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $verb = $preview ? 'Preview' : 'Import';
        $summary = "{$verb}: {$log->imported} row(s) ".($preview ? 'would be imported' : 'imported')
            .", {$log->skipped} skipped.";

        return back()
            ->with('success', $summary)
            ->with('import_errors', $log->errors);
    }
}

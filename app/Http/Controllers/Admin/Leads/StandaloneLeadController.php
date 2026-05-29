<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Http\Controllers\Controller;
use App\Modules\Leads\Models\CrmStandaloneLead;
use App\Modules\Leads\Services\StandaloneLeadImportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StandaloneLeadController extends Controller
{
    public function index(Request $request): View
    {
        $leads = CrmStandaloneLead::query()
            ->with(['creator', 'assignedTo', 'salesManager'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.leads.standalone.index', compact('leads'));
    }

    public function create(): View
    {
        return view('admin.leads.standalone.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        CrmStandaloneLead::query()->create([
            ...$validated,
            'created_by' => $request->user('admin')->id,
        ]);

        return redirect()->route('admin.standalone-leads.index')
            ->with('success', 'Marketing lead created.');
    }

    public function importForm(): View
    {
        return view('admin.leads.standalone.import');
    }

    public function import(Request $request, StandaloneLeadImportExportService $service): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $service->importCsv($request->file('file'), $request->user('admin'));

        return redirect()->route('admin.standalone-leads.index')
            ->with('success', "Imported {$result['imported']} lead(s). Skipped {$result['skipped']}.");
    }

    public function export(StandaloneLeadImportExportService $service): StreamedResponse
    {
        return $service->exportCsv();
    }

    public function template(StandaloneLeadImportExportService $service): StreamedResponse
    {
        return $service->downloadTemplate();
    }
}

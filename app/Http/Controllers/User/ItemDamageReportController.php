<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ItemDamageReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemDamageReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'item_instance_id' => 'required|exists:item_instances,id',
            'borrow_request_id' => 'nullable|exists:borrow_requests,id',
            'description' => 'required|string|max:2000',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'file|image|max:5120',
        ]);

        $storedPhotos = [];
        try {
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $storedPhotos[] = $photo->store('damage-reports', 'public');
                }
            } elseif (! empty($data['photos']) && is_array($data['photos'])) {
                foreach ($data['photos'] as $value) {
                    if (is_string($value) && trim($value) !== '') {
                        $storedPhotos[] = $value;
                    }
                }
            }

            $report = ItemDamageReport::create([
                'item_instance_id' => $data['item_instance_id'],
                'borrow_request_id' => $data['borrow_request_id'] ?? null,
                'reported_by' => $request->user()->id,
                'description' => $data['description'],
                'photos' => $storedPhotos ?: null,
                'status' => 'reported',
            ]);

            $report->load(['itemInstance.item', 'borrowRequest', 'reporter']);

            return response()->json($report, 201);
        } catch (\Throwable $e) {
            if (! empty($storedPhotos)) {
                foreach ($storedPhotos as $path) {
                    if ($request->hasFile('photos')) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }

            throw $e;
        }
    }
}

<?php

use App\Models\Notice;
use Illuminate\Http\Request;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class NoticeController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $notices = Notice::active()
            ->when(!$user->is_superadmin, fn($q) => $q->where('superadmin_only', false))
            ->latest()
            ->get();

        return view('notices.index', compact('notices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'superadmin_only' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $validated['created_by'] = auth()->id();

        Notice::create($validated);
        ToastMagic::success('Notice created successfully.');
        return redirect()->back();
    }

    // Add other methods as needed: show, edit, update, destroy
}


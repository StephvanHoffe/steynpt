<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Aanvragen voor een gratis kennismaking via de contactpagina. */
class RequestController extends Controller
{
    public function index(Request $request): View
    {
        $done = $request->query('toon') === 'afgehandeld';

        return view('admin.requests', [
            'done' => $done,
            'rows' => ContactRequest::query()->where('handled', $done)->orderByDesc('created_at')->orderByDesc('id')->limit(200)->get(),
            'openCount' => ContactRequest::query()->where('handled', false)->count(),
            'doneCount' => ContactRequest::query()->where('handled', true)->count(),
        ]);
    }

    /** Afgehandeld, of weer terug naar open. */
    public function toggle(Request $request): RedirectResponse
    {
        $id = filter_var($request->input('id'), FILTER_VALIDATE_INT);
        $contact = $id !== false ? ContactRequest::query()->find($id) : null;
        if ($contact) {
            $contact->update(['handled' => ! $contact->handled]);
        }

        return back();
    }
}

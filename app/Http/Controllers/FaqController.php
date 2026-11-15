<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->validate(['q' => 'nullable|string|max:200'])['q'] ?? '';
        $faqs = Faq::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('question', 'like', '%'.$search.'%')
                ->orWhere('answer', 'like', '%'.$search.'%')))
            ->orderBy('sort_order')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.faq.index', compact('faqs', 'search'));
    }

    public function create() { return view('admin.faq.form', ['faq' => new Faq(['is_active' => true, 'sort_order' => 0])]); }

    public function store(Request $request)
    {
        Faq::create($this->validated($request));
        return redirect()->route('admin.faq.index')->with('success', 'FAQ berhasil ditambahkan.');
    }

    public function edit(Faq $faq) { return view('admin.faq.form', compact('faq')); }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->validated($request));
        return redirect()->route('admin.faq.index')->with('success', 'FAQ berhasil diperbarui.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return redirect()->route('admin.faq.index')->with('success', 'FAQ berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:20000',
            'sort_order' => 'required|integer|min:0|max:999999',
            'is_active' => 'required|boolean',
        ]);
    }
}


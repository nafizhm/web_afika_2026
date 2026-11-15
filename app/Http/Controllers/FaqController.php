<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->validate(['q' => 'nullable|string|max:200'])['q'] ?? '';
        $audience = $request->validate(['audience'=>'nullable|in:ai,web,both'])['audience'] ?? '';
        $faqs = Faq::query()->audience($audience)
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('question', 'like', '%'.$search.'%')
                ->orWhere('answer', 'like', '%'.$search.'%')))
            ->orderBy('sort_order')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.faq.index', compact('faqs', 'search', 'audience'));
    }

    public function create() { return view('admin.faq.form', ['faq' => new Faq(['is_active' => true, 'sort_order' => 0, 'is_public'=>true, 'faq_type'=>'general'])]); }

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
        $data = $request->validate([
            'faq_type'=>'sometimes|required|in:general,identity,handoff',
            'is_public'=>'sometimes|required|boolean',
            'audience'=>'sometimes|required|in:ai,web,both',
            'faq_type'=>'sometimes|required|in:general,identity,handoff',
            'is_public'=>'sometimes|required|boolean',
            'audience'=>'sometimes|required|in:ai,web,both',
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:20000',
            'sort_order' => 'required|integer|min:0|max:999999',
            'is_active' => 'required|boolean',
        ]);
        if (($data['faq_type'] ?? 'general') !== 'general') $data['is_public'] = false;
        if (($data['faq_type'] ?? '') === 'identity' && mb_strlen($data['answer']) > 60) {
            throw \Illuminate\Validation\ValidationException::withMessages(['answer'=>'Isi nama asisten saja, maksimal 60 karakter.']);
        }
        return $data;
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        return view('admin.faqs', ['faqs' => Faq::orderBy('sort')->get()]);
    }

    public function store(Request $request)
    {
        Faq::create($this->data($request));

        return back()->with('ok', 'أضيف السؤال');
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->data($request));

        return back()->with('ok', 'حُفظ السؤال');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return back()->with('ok', 'حُذف السؤال');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'question' => 'required|string|max:200',
            'answer' => 'required|string|max:3000',
            'sort' => 'nullable|integer|min:0|max:999',
        ], [], ['question' => 'السؤال', 'answer' => 'الجواب']) + ['is_active' => $request->boolean('is_active')];
    }
}

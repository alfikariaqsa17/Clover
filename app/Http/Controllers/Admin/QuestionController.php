<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductQuestion;
use App\Support\Msg;
use App\Support\Notify;
use App\Support\SafeMail;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $r)
    {
        $filter = (string) $r->query('status', 'belum');
        $questions = ProductQuestion::with('product', 'user')
            ->when($filter === 'belum', fn ($q) => $q->whereNull('answer'))
            ->when($filter === 'sudah', fn ($q) => $q->whereNotNull('answer'))
            ->orderByDesc('id')->paginate(12)->withQueryString();

        return view('admin.questions.index', ['questions' => $questions, 'filter' => $filter]);
    }

    public function answer(Request $r, ProductQuestion $question)
    {
        $d = $r->validate(['answer' => 'required|string|min:3|max:1000'], Msg::messages(), Msg::attributes() + ['answer' => 'Jawaban']);
        $first = $question->answer === null;
        $question->update(['answer' => $d['answer'], 'answered_at' => now()]);

        if ($first && $question->user) {
            $url = route('products.show', $question->product) . '#tanya-jawab';
            Notify::to($question->user_id, 'info', 'Pertanyaan Anda dijawab', $question->product->name, $url);
            SafeMail::notice($question->user, 'Pertanyaan Anda tentang ' . $question->product->name . ' sudah dijawab', 'Pertanyaan Anda dijawab',
                ['Pertanyaan: ' . $question->question, 'Jawaban: ' . $d['answer']], 'Lihat di halaman produk', $url);
        }

        return back()->with('toast', 'Jawaban disimpan.');
    }

    public function toggle(ProductQuestion $question)
    {
        $question->update(['is_visible' => ! $question->is_visible]);

        return back()->with('toast', $question->is_visible ? 'Pertanyaan ditampilkan.' : 'Pertanyaan disembunyikan.');
    }

    public function destroy(ProductQuestion $question)
    {
        $question->delete();

        return back()->with('toast', 'Pertanyaan dihapus.');
    }
}

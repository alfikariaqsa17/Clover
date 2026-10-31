<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $r)
    {
        $q = Article::published()->with('category');
        $kat = (string) $r->query('kategori', '');
        if ($kat !== '') {
            $q->whereHas('category', fn ($c) => $c->where('slug', $kat));
        }
        $s = trim((string) $r->query('q', ''));
        if ($s !== '') {
            $q->where('title', 'like', '%' . $s . '%');
        }

        return view('tutorial.index', [
            'articles' => $q->orderByDesc('published_at')->paginate(9)->withQueryString(),
            'categories' => ArticleCategory::orderBy('name')->get(),
        ]);
    }

    public function show(Article $article)
    {
        abort_unless($article->is_published, 404);

        return view('tutorial.show', [
            'article' => $article->load('category'),
            'others' => Article::published()->with('category')->where('id', '!=', $article->id)->orderByDesc('published_at')->take(3)->get(),
        ]);
    }
}

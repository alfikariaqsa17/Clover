<?php

namespace App\Http\Controllers\Admin;

use App\Models\Article;
use App\Models\ArticleCategory;

class ArticleController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'model' => Article::class, 'title' => 'Artikel', 'route' => 'admin.articles',
            'heading' => 'Tips & Artikel', 'search' => ['title'], 'order' => ['published_at', 'desc'], 'slug_from' => 'title',
            'with' => ['category'], 'image' => ['field' => 'image', 'dir' => 'articles'],
            'columns' => [['Foto', 'image_url', 'image'], ['Judul', 'title', 'text'], ['Kategori', 'category.name', 'text'], ['Terbit', 'published_at', 'date'], ['Tayang', 'is_published', 'bool']],
            'fields' => [
                ['name' => 'title', 'label' => 'Judul'],
                ['name' => 'article_category_id', 'label' => 'Kategori', 'type' => 'select', 'options' => ArticleCategory::orderBy('name')->pluck('name', 'id')->all(), 'assoc' => true],
                ['name' => 'published_at', 'label' => 'Tanggal Terbit', 'type' => 'date', 'nomin' => true],
                ['name' => 'excerpt', 'label' => 'Ringkasan', 'type' => 'textarea'],
                ['name' => 'body', 'label' => 'Isi Artikel (pisahkan paragraf dengan baris kosong)', 'type' => 'textarea'],
                ['name' => 'is_published', 'label' => 'Tayangkan', 'type' => 'checkbox'],
                ['name' => 'image', 'label' => 'Foto Artikel', 'type' => 'file', 'optional' => true],
            ],
            'rules' => fn () => [
                'title' => 'required|string|min:3|max:150',
                'article_category_id' => 'required|integer|exists:article_categories,id',
                'published_at' => 'required|date',
                'excerpt' => 'required|string|min:5|max:300',
                'body' => 'required|string|min:20|max:10000',
                'is_published' => 'boolean',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            ],
        ];
    }

    protected function defaults(): array
    {
        return ['published_at' => now()->format('Y-m-d'), 'is_published' => true];
    }
}

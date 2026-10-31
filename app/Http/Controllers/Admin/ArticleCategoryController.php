<?php

namespace App\Http\Controllers\Admin;

use App\Models\ArticleCategory;
use Illuminate\Validation\Rule;

class ArticleCategoryController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'model' => ArticleCategory::class, 'title' => 'Kategori artikel', 'route' => 'admin.article-categories',
            'heading' => 'Kategori Artikel', 'search' => ['name'], 'order' => ['name', 'asc'], 'slug_from' => 'name',
            'columns' => [['Nama', 'name', 'text'], ['Slug', 'slug', 'text']],
            'fields' => [['name' => 'name', 'label' => 'Nama Kategori']],
            'rules' => fn () => ['name' => 'required|string|min:2|max:60'],
        ];
    }
}

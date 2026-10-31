<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Plant;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\Msg;
use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProductController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'model' => Product::class, 'title' => 'Produk', 'route' => 'admin.products',
            'heading' => 'Produk, Stok & Harga', 'search' => ['name'], 'order' => ['id', 'desc'], 'slug_from' => 'name',
            'with' => ['category', 'images'], 'multipart' => true,
            'extra_in_form' => 'admin.products._upload', 'extra_after' => 'admin.products._gallery',
            'columns' => [['Foto', 'image_url', 'image'], ['Nama', 'name', 'text'], ['Kategori', 'category.name', 'text'], ['Harga', 'price', 'money'], ['Stok', 'stock', 'text'], ['Terjual', 'sold_count', 'text'], ['Aktif', 'is_active', 'bool']],
            'fields' => [
                ['name' => 'name', 'label' => 'Nama Produk'],
                ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'options' => Category::orderBy('sort_order')->pluck('name', 'id')->all(), 'assoc' => true],
                ['name' => 'plant_id', 'label' => 'Data Tanaman (untuk info perawatan)', 'type' => 'select', 'options' => Plant::orderBy('name')->pluck('name', 'id')->all(), 'assoc' => true, 'optional' => true, 'hint' => 'Kosongkan untuk perlengkapan/non-tanaman.'],
                ['name' => 'price', 'label' => 'Harga (Rp)', 'type' => 'number', 'min' => 0],
                ['name' => 'stock', 'label' => 'Stok', 'type' => 'number', 'min' => 0],
                ['name' => 'size', 'label' => 'Ukuran tanaman / pot', 'optional' => true],
                ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ['name' => 'is_active', 'label' => 'Tampil di toko', 'type' => 'checkbox'],
                ['name' => 'is_featured', 'label' => 'Produk unggulan (beranda)', 'type' => 'checkbox'],
                ['name' => 'is_plant_of_week', 'label' => 'Jadikan Plant of the Week', 'type' => 'checkbox'],
            ],
            'rules' => fn () => [
                'name' => 'required|string|min:2|max:120',
                'category_id' => 'required|integer|exists:categories,id',
                'plant_id' => 'nullable|integer|exists:plants,id',
                'price' => 'required|integer|min:0|max:100000000',
                'stock' => 'required|integer|min:0|max:100000',
                'size' => 'nullable|string|max:100',
                'description' => 'required|string|min:5|max:2000',
                'is_active' => 'boolean',
                'is_featured' => 'boolean',
                'is_plant_of_week' => 'boolean',
                'images' => 'nullable|array|max:8',
                'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:3072',
            ],
        ];
    }

    protected function defaults(): array
    {
        return ['stock' => 10, 'is_active' => true];
    }

    protected function viewData(?Model $model): array
    {
        return ['product' => $model];
    }

    protected function prepare(array $data, ?Model $model, Request $r): array
    {
        $data['plant_id'] = $data['plant_id'] ?? null;

        return $data;
    }

    protected function after(Model $model, Request $r, bool $created): void
    {
        if ($model->is_plant_of_week) {
            Product::where('id', '!=', $model->id)->update(['is_plant_of_week' => false]);
        }
        $this->storeImages($model, $r);
    }

    private function storeImages(Product $product, Request $r): void
    {
        if (! $r->hasFile('images')) {
            return;
        }
        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        $order = (int) $product->images()->max('sort_order');
        foreach ($r->file('images') as $file) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => Upload::save($file, 'products'),
                'alt' => $product->name,
                'sort_order' => ++$order,
                'is_primary' => ! $hasPrimary,
            ]);
            $hasPrimary = true;
        }
    }

    public function imageStore(Request $r, Product $product)
    {
        $r->validate([
            'images' => 'required|array|max:8',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:3072',
        ], Msg::messages(), Msg::attributes());
        $this->storeImages($product, $r);

        return back()->with('toast', 'Foto produk ditambahkan.');
    }

    public function imagePrimary(ProductImage $image)
    {
        ProductImage::where('product_id', $image->product_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('toast', 'Foto utama diperbarui.');
    }

    public function imageDestroy(ProductImage $image)
    {
        $productId = $image->product_id;
        Upload::delete($image->path, 'products');
        $wasPrimary = $image->is_primary;
        $image->delete();
        if ($wasPrimary && ($next = ProductImage::where('product_id', $productId)->orderBy('sort_order')->first())) {
            $next->update(['is_primary' => true]);
        }

        return back()->with('toast', 'Foto dihapus.');
    }

    public function destroy($id)
    {
        $product = $this->find($id);
        foreach ($product->images as $img) {
            Upload::delete($img->path, 'products');
        }

        return parent::destroy($id);
    }
}

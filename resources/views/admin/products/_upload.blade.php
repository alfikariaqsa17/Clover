<div class="field">
    <label for="a-images">{{ $model->exists ? 'Tambah foto produk' : 'Foto produk' }} (JPG/PNG/WEBP, maks 3 MB per foto, hingga 8)</label>
    <input id="a-images" type="file" name="images[]" multiple accept="image/png,image/jpeg,image/webp" @error('images') aria-invalid="true" @enderror @error('images.*') aria-invalid="true" @enderror>
    <small class="hint">Foto pertama menjadi foto utama. Anda dapat mengatur foto utama dan menghapus foto setelah produk disimpan.</small>
    @error('images')<small class="error" role="alert">{{ $message }}</small>@enderror
    @error('images.*')<small class="error" role="alert">{{ $message }}</small>@enderror
</div>

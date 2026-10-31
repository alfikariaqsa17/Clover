<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Msg;
use App\Support\Upload;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Basis CRUD admin. Subclass cukup mendefinisikan cfg():
 *  model, title, route, columns[[label,key,type]], fields[[name,label,type,...]],
 *  rules(?Model): array, search[], with[], order[col,dir], slug_from, image[field,dir]
 */
abstract class CrudController extends Controller
{
    abstract protected function cfg(): array;

    protected function prepare(array $data, ?Model $model, Request $r): array
    {
        return $data;
    }

    protected function after(Model $model, Request $r, bool $created): void
    {
    }

    protected function values(Model $model): array
    {
        $out = [];
        foreach ($this->cfg()['fields'] as $f) {
            $v = $model->getAttribute($f['name']);
            if ($v instanceof CarbonInterface) {
                $v = ($f['type'] ?? '') === 'datetime-local' ? $v->format('Y-m-d\TH:i') : $v->format('Y-m-d');
            }
            $out[$f['name']] = $v;
        }

        return $out;
    }

    protected function find($id): Model
    {
        $class = $this->cfg()['model'];
        $probe = new $class;

        return $class::where($probe->getRouteKeyName(), $id)->firstOrFail();
    }

    protected function viewData(?Model $model): array
    {
        return [];
    }

    public function index(Request $r)
    {
        $cfg = $this->cfg();
        $class = $cfg['model'];
        $q = $class::query()->with($cfg['with'] ?? []);
        $s = trim((string) $r->query('q', ''));
        if ($s !== '' && ! empty($cfg['search'])) {
            $q->where(function ($w) use ($cfg, $s) {
                foreach ($cfg['search'] as $col) {
                    $w->orWhere($col, 'like', '%' . $s . '%');
                }
            });
        }
        [$col, $dir] = $cfg['order'] ?? ['id', 'desc'];
        $rows = $q->orderBy($col, $dir)->paginate(15)->withQueryString();

        return view('admin.crud.index', ['cfg' => $cfg, 'rows' => $rows, 'q' => $s]);
    }

    public function create()
    {
        $cfg = $this->cfg();
        $model = new $cfg['model'];

        return view('admin.crud.form', ['cfg' => $cfg, 'model' => $model, 'values' => $this->defaults()] + $this->viewData(null));
    }

    protected function defaults(): array
    {
        return [];
    }

    public function store(Request $r)
    {
        $cfg = $this->cfg();
        $data = $r->validate($cfg['rules'](null), Msg::messages(), Msg::attributes());
        $data = $this->clean($data, $cfg);

        if (! empty($cfg['slug_from'])) {
            $base = Str::slug($data[$cfg['slug_from']] ?? '') ?: 'item';
            $slug = $base;
            $i = 2;
            while ($cfg['model']::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $data['slug'] = $slug;
        }

        $data = $this->prepare($data, null, $r);
        if (! empty($cfg['image']) && $r->hasFile($cfg['image']['field'])) {
            $data[$cfg['image']['field']] = Upload::save($r->file($cfg['image']['field']), $cfg['image']['dir']);
        }
        $model = $cfg['model']::create($data);
        $this->after($model, $r, true);

        return redirect()->route($cfg['route'] . '.index')->with('toast', $cfg['title'] . ' berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $cfg = $this->cfg();
        $model = $this->find($id);

        return view('admin.crud.form', ['cfg' => $cfg, 'model' => $model, 'values' => $this->values($model)] + $this->viewData($model));
    }

    public function update(Request $r, $id)
    {
        $cfg = $this->cfg();
        $model = $this->find($id);
        $data = $r->validate($cfg['rules']($model), Msg::messages(), Msg::attributes());
        $data = $this->clean($data, $cfg);
        $data = $this->prepare($data, $model, $r);

        if (! empty($cfg['image']) && $r->hasFile($cfg['image']['field'])) {
            Upload::delete($model->{$cfg['image']['field']}, $cfg['image']['dir']);
            $data[$cfg['image']['field']] = Upload::save($r->file($cfg['image']['field']), $cfg['image']['dir']);
        }
        $model->update($data);
        $this->after($model, $r, false);

        return redirect()->route($cfg['route'] . '.index')->with('toast', $cfg['title'] . ' berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $cfg = $this->cfg();
        $model = $this->find($id);
        if (! empty($cfg['image'])) {
            Upload::delete($model->{$cfg['image']['field']}, $cfg['image']['dir']);
        }
        $model->delete();

        return redirect()->route($cfg['route'] . '.index')->with('toast', $cfg['title'] . ' dihapus.');
    }

    /** Buang field file & field yang bukan kolom (mis. images[]). */
    private function clean(array $data, array $cfg): array
    {
        foreach ($cfg['fields'] as $f) {
            if (($f['type'] ?? '') === 'file') {
                unset($data[$f['name']]);
            }
        }
        unset($data['images']);

        return $data;
    }
}

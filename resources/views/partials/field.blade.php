@php
    $type = $type ?? 'text';
    $id = ($prefix ?? 'f') . '-' . $name;
    $val = $type === 'password' ? '' : old($name, $value ?? '');
    $req = ! ($optional ?? false);
@endphp
@if($type === 'checkbox')
    <div class="field">
        <label class="check">
            <input type="hidden" name="{{ $name }}" value="0">
            <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked((bool) old($name, $value ?? false))>
            <span>{{ $label }}</span>
        </label>
        @error($name)<small class="error" role="alert">{{ $message }}</small>@enderror
    </div>
@else
    <div class="field">
        <label for="{{ $id }}">{{ $label }}{{ $req ? ' *' : '' }}</label>
        @if($type === 'textarea')
            <textarea id="{{ $id }}" name="{{ $name }}" @if($req) required @endif @error($name) aria-invalid="true" @enderror>{{ $val }}</textarea>
        @elseif($type === 'select')
            <select id="{{ $id }}" name="{{ $name }}" @if($req) required @endif @error($name) aria-invalid="true" @enderror>
                <option value="">Pilih...</option>
                @foreach($options as $ov => $ol)
                    @php($optVal = ($assoc ?? false) ? $ov : $ol)
                    <option value="{{ $optVal }}" @selected((string) $val === (string) $optVal)>{{ $ol }}</option>
                @endforeach
            </select>
        @elseif($type === 'file')
            <input id="{{ $id }}" type="file" name="{{ $name }}" accept="image/png,image/jpeg,image/webp" @isset($preview) data-preview="{{ $preview }}" @endisset @error($name) aria-invalid="true" @enderror>
        @else
            <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $val }}" @if($req) required @endif
                @if($type === 'date' && !($nomin ?? false)) min="{{ date('Y-m-d') }}" @endif
                @if($type === 'number') min="{{ $min ?? 1 }}" @if(isset($step)) step="{{ $step }}" @endif @endif
                @if(!empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif
                @error($name) aria-invalid="true" @enderror>
        @endif
        @if(!empty($hint))<small class="hint">{{ $hint }}</small>@endif
        @error($name)<small class="error" role="alert">{{ $message }}</small>@enderror
    </div>
@endif

@props(['name','label','type'=>'text','value'=>null,'options'=>[],'required'=>false,'hint'=>null,'scope'=>null])
@php($key=trim(str_replace(['[',']'],['.',''],$name),'.'))
@php($id='field-'.str_replace('.','-',$key).'-'.\Illuminate\Support\Str::random(6))
@php($inputValue=old('_form')===$scope?old($key,$value):$value)
<div class="field"><label for="{{ $id }}">{{ $label }} @if($required)<span class="required" aria-hidden="true">*</span>@endif</label>
@if($type==='select')<select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes }} @if($errors->has($key)) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif><option value="">{{ $required?'Pilih…':'Belum diisi / belum diketahui' }}</option>@foreach($options as $val=>$text)<option value="{{ $val }}" @selected((string)$inputValue===(string)$val)>{{ $text }}</option>@endforeach</select>
@elseif($type==='textarea')<textarea id="{{ $id }}" name="{{ $name }}" rows="3" @required($required) {{ $attributes }} @if($errors->has($key)) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>{{ $inputValue }}</textarea>
@else<input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type==='password'?'':$inputValue }}" @required($required) {{ $attributes }} @if($errors->has($key)) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
@endif
@if($hint)<small class="field-hint">{{ $hint }}</small>@endif
@error($key)<small class="field-error" id="{{ $id }}-error">{{ $message }}</small>@enderror</div>

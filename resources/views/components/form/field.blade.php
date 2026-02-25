@props(['label' => false, 'name', 'type' => 'text', 'value' => ''])

<div>
    @if ($label)
    <label for="{{ $name }}">{{ $label }}</label>
    @endif

    @if ($type === 'textarea')
        <textarea
            name="{{ $name }}"
            id="{{ $name }}"
            class="textarea resize-none mt-2
                focus:outline-none ring-0
                focus:ring-2 focus:ring-indigo-600
                not-placeholder-shown:ring-2 not-placeholder-shown:ring-primary
                not-placeholder-shown:focus:ring-indigo-600" {{ $attributes }}>{{ old($name, $value) }}</textarea>
    @else

    <input 
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        class="input mt-2
               focus:outline-none
               ring-0
               focus:ring-2 focus:ring-indigo-600
               not-placeholder-shown:ring-2 not-placeholder-shown:ring-primary
               not-placeholder-shown:focus:ring-indigo-600
               @error($name) ring-2 ring-red-500 focus:ring-red-500 @enderror"
        {{ $attributes }}>
    @endif

    <x-form.error name="{{ $name }}"/>
</div>

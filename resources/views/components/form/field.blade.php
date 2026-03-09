@props(['label' => false, 'name', 'type' => 'text', 'value' => ''])

<div>
    @if ($label)
    <label for="{{ $name }}">{{ $label }}</label>
    @endif

    @if ($type === 'textarea')
        <x-form.textarea
            name="{{ $name }}"
            id="{{ $name }}"
            class="resize-none mt-2"
            {{ $attributes }}
        >{{ old($name, $value) }}</x-form.textarea>
    @else

    <x-form.input
        :type="$type"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        class="mt-2"
        {{ $attributes }}
    />
    @endif

    <x-form.error name="{{ $name }}"/>
</div>

@props([
    'label',
    'name',
    'options' => [],
    'placeholder' => 'Semua',
    'type' => 'select',
])

@php
    $value = request($name);
    $values = array_filter((array) $value);
    $resetQuery = array_filter(array_diff_key(request()->query(), [$name => '']), fn ($value) => ! blank($value));
    $resetUrl = $resetQuery === []
        ? url()->current()
        : url()->current().'?'.http_build_query($resetQuery);
    $controlName = $type === 'checkbox' ? $name.'[]' : $name;
@endphp

<details class="group relative border-b border-black/10 lg:flex lg:h-[3.125rem] lg:flex-col lg:border-0">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 py-4 text-label text-ink lg:flex-1 lg:rounded-control lg:bg-primary lg:px-4 lg:py-3 lg:text-white">
        {{ $label }}
        <x-icons.chevron-right class="h-2.5 w-2.5 transition-transform rotate-90 group-open:-rotate-90" />
    </summary>

    <div class="pb-4 lg:absolute lg:right-0 lg:top-full lg:z-20 lg:mt-2 lg:w-60 lg:rounded-control lg:border-[0.5px] lg:border-line lg:bg-white lg:p-3 lg:shadow-lg">
        <div class="mb-2 flex justify-end">
            <a href="{{ $resetUrl }}" class="text-label text-primary hover:underline">Reset</a>
        </div>

        @if ($type === 'checkbox')
            <div class="space-y-2">
                @foreach ($options as $optionValue => $optionLabel)
                    <label class="flex items-center gap-2.5 text-body">
                        <input
                            type="checkbox"
                            name="{{ $controlName }}"
                            value="{{ $optionValue }}"
                            @checked(in_array($optionValue, $values, true))
                            class="h-4 w-4 rounded-control border-line accent-primary"
                        />
                        {{ $optionLabel }}
                    </label>
                @endforeach
            </div>
        @elseif ($type === 'date')
            <input
                type="date"
                name="{{ $controlName }}"
                value="{{ $value }}"
                class="w-full rounded-control border-[0.5px] border-line bg-white px-3 py-2.5 text-body outline-none focus:border-primary"
            />
        @else
            <select
                name="{{ $controlName }}"
                class="w-full rounded-control border-[0.5px] border-line bg-white px-3 py-2.5 text-body outline-none focus:border-primary"
            >
                <option value="">{{ $placeholder }}</option>
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected($value == $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @endif
    </div>
</details>

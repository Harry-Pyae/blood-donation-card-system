@php
    $fieldValue = (string) old($name, $selected ?? '');
    $fieldRequired = (bool) ($required ?? false);
    $fieldIcon = $icon ?? 'la-list';
    $selectedLabel = $options[$fieldValue] ?? reset($options);
@endphp
<div class="bc-haemo-select-field">
    <label id="{{ $id }}-label" for="{{ $id }}">{{ $label }}</label>
    <div class="bc-filter-dropdown bc-haemo-dropdown" data-bc-national-select>
        <i class="la {{ $fieldIcon }} bc-filter-dropdown-icon" aria-hidden="true"></i>
        <select class="visually-hidden" id="{{ $id }}" name="{{ $name }}" tabindex="-1" aria-labelledby="{{ $id }}-label" @required($fieldRequired)>
            @foreach($options as $optionValue => $optionLabel)<option value="{{ $optionValue }}" @selected($fieldValue === (string) $optionValue)>{{ $optionLabel }}</option>@endforeach
        </select>
        <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $id }}-menu" @if($fieldRequired) aria-required="true" @endif><span data-bc-select-label>{{ $selectedLabel }}</span><i class="la la-angle-down" aria-hidden="true"></i></button>
        <div class="bc-filter-dropdown-menu" id="{{ $id }}-menu" role="listbox" aria-labelledby="{{ $id }}-label" hidden>
            @foreach($options as $optionValue => $optionLabel)<button type="button" role="option" data-value="{{ $optionValue }}" aria-selected="{{ $fieldValue === (string) $optionValue ? 'true' : 'false' }}"><span>{{ $optionLabel }}</span><i class="la la-check" aria-hidden="true"></i></button>@endforeach
        </div>
    </div>
</div>

<div {{ $attributes->merge(['class' => 'nrc-selector']) }} data-nrc-selector data-language="{{ $language }}" id="{{ $id }}">
    @foreach (['state', 'township', 'type'] as $field)
        <div>
            <label for="{{ $id }}-{{ $field }}" data-label="{{ $field }}">{{ $text[$field] }}</label>
            <select id="{{ $id }}-{{ $field }}" data-field="{{ $field }}" @if($required) required @endif @if($disabled) disabled @endif aria-invalid="{{ $error ? 'true' : 'false' }}" @if($error) aria-describedby="{{ $id }}-error" @endif>
                <option value="">{{ $text['choose'] }}</option>
                @foreach ($options[$field] as $option)
                    <option value="{{ $option['value'] }}" @if($parts[$field] === $option['value']) selected @endif>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>
    @endforeach

    <div>
        <label for="{{ $id }}-number" data-label="number">{{ $text['number'] }}</label>
        <input type="text" id="{{ $id }}-number" data-field="number" inputmode="numeric" autocomplete="off" pattern="[0-9၀-၉]{6}" maxlength="6" placeholder="{{ $text['placeholder'] }}" value="{{ \Thuraaung\NrcKit\NrcData::numbers($parts['number'], $language) }}" @if($required) required @endif @if($disabled) disabled @endif aria-invalid="{{ $error ? 'true' : 'false' }}" @if($error) aria-describedby="{{ $id }}-error" @endif>
    </div>

    <input type="hidden" name="{{ $name }}" value="{{ $formatted }}" data-nrc-value @if($disabled) disabled @endif>

    <span id="{{ $id }}-error" role="alert">{{ $error }}</span>

    <script type="application/json" data-nrc-data>{!! json_encode($reference, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
</div>

{{-- A view stack scopes the asset to one render, including on Laravel 7.0. --}}
@if ($__env->yieldPushContent('nrc-kit-assets-loaded') === '')
    @push('nrc-kit-assets-loaded', '1')
    <script src="{{ asset('vendor/nrc-kit/nrc-selector.js') }}" defer></script>
@endif

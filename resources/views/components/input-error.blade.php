@props(['field'])

@error($field)
    <div class="text-danger mt-1">
        {{ $message }}
    </div>
@enderror

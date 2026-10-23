@props(['name'])
@error($name)
    <p class="error-text">{{ $message }}</p>
@enderror

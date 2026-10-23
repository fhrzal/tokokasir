@props(['product'])
@switch($product->stockStatus())
    @case('habis')
        <span class="badge badge-red"><x-icon name="x-circle" class="size-3.5" />Habis</span>
        @break
    @case('menipis')
        <span class="badge badge-yellow"><x-icon name="alert" class="size-3.5" />Menipis</span>
        @break
    @default
        <span class="badge badge-green"><x-icon name="check-circle" class="size-3.5" />Aman</span>
@endswitch

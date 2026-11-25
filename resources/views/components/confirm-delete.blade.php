@props(['action', 'label' => 'Delete', 'message' => 'Delete this record?'])
<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm(@js($message))">
    @csrf @method('DELETE')
    <button {{ $attributes->class(['btn btn-sm btn-danger']) }}>{{ $label }}</button>
</form>

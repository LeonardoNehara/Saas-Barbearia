@props(['value' => 10])
<div class="ml-auto flex items-center gap-3">
    <label for="per_page" class="text-xs text-muted">Exibir</label>
    <select id="per_page" name="per_page" class="admin-input w-20">
        @foreach ([10, 15, 25, 50] as $amount)
            <option value="{{ $amount }}" @selected((int) $value === $amount)>{{ $amount }}</option>
        @endforeach
    </select>
</div>

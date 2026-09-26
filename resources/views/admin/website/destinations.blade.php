@extends('layouts.admin')

@section('title', 'Homepage Destinations')

@section('content')
<x-admin.page-header title="Homepage Destinations" description="Choose which destinations appear in the “Popular Destinations” row on the homepage, their order and photos." icon="W" eyebrow="Website" />

@include('admin.website._tabs')

<form method="POST" action="{{ route('admin.website.destinations.update') }}" enctype="multipart/form-data" class="egh-card wsc-section" id="destinationsForm">
    @csrf
    @method('PUT')

    <div class="wsc-table-wrap">
        <table class="wsc-table">
            <thead>
                <tr><th>Photo</th><th>Name</th><th>Country</th><th>Image</th><th>Order</th><th>Show</th><th>Remove</th></tr>
            </thead>
            <tbody id="destinationRows">
                @foreach (old('items', $destinations) as $index => $destination)
                    <tr>
                        <td>
                            @if (! empty($destination['url']))
                                <img class="wsc-thumb" src="{{ $destination['url'] }}" alt="">
                            @endif
                        </td>
                        <td><input name="items[{{ $index }}][name]" value="{{ $destination['name'] ?? '' }}" maxlength="60" required aria-label="Destination name" @disabled(! $canManage)></td>
                        <td><input name="items[{{ $index }}][country]" value="{{ $destination['country'] ?? '' }}" maxlength="60" aria-label="Country" @disabled(! $canManage)></td>
                        <td class="wsc-dest-image">
                            <select name="items[{{ $index }}][image]" aria-label="Image" @disabled(! $canManage)>
                                <option value="">No image</option>
                                @foreach ($imageOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(($destination['image'] ?? '') === $key)>{{ $label }}</option>
                                @endforeach
                                @if (! empty($destination['image']) && ! array_key_exists($destination['image'], $imageOptions))
                                    <option value="{{ $destination['image'] }}" selected>Uploaded photo</option>
                                @endif
                            </select>
                            @if ($canManage)
                                <input type="file" name="items[{{ $index }}][upload]" accept="image/jpeg,image/png,image/webp" aria-label="Upload a new photo">
                            @endif
                        </td>
                        <td><input type="number" name="items[{{ $index }}][sort]" value="{{ $destination['sort'] ?? $index }}" min="0" max="999" class="wsc-num" aria-label="Order" @disabled(! $canManage)></td>
                        <td>
                            <input type="hidden" name="items[{{ $index }}][active]" value="0">
                            <label class="wsc-switch"><input type="checkbox" name="items[{{ $index }}][active]" value="1" @checked($destination['active'] ?? true) @disabled(! $canManage)><span></span><span class="sr-only">Show on homepage</span></label>
                        </td>
                        <td>
                            <label class="wsc-remove"><input type="checkbox" name="items[{{ $index }}][remove]" value="1" @disabled(! $canManage)> Remove</label>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($canManage)
        <template id="destinationTemplate">
            <tr>
                <td></td>
                <td><input name="items[__i__][name]" maxlength="60" required aria-label="Destination name"></td>
                <td><input name="items[__i__][country]" maxlength="60" aria-label="Country"></td>
                <td class="wsc-dest-image">
                    <select name="items[__i__][image]" aria-label="Image">
                        <option value="">No image</option>
                        @foreach ($imageOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="file" name="items[__i__][upload]" accept="image/jpeg,image/png,image/webp" aria-label="Upload a new photo">
                </td>
                <td><input type="number" name="items[__i__][sort]" value="__i__" min="0" max="999" class="wsc-num" aria-label="Order"></td>
                <td><input type="hidden" name="items[__i__][active]" value="0"><label class="wsc-switch"><input type="checkbox" name="items[__i__][active]" value="1" checked><span></span><span class="sr-only">Show on homepage</span></label></td>
                <td><label class="wsc-remove"><input type="checkbox" name="items[__i__][remove]" value="1"> Remove</label></td>
            </tr>
        </template>

        <div class="admin-form-actions">
            <button type="button" class="egh-button secondary" id="addDestination">Add destination</button>
            <button type="submit" class="egh-button">Save destinations</button>
        </div>
    @endif
</form>

@endsection

@section('scripts')
@if ($canManage)
        <script>
            (function () {
                var add = document.getElementById('addDestination');
                var rows = document.getElementById('destinationRows');
                var template = document.getElementById('destinationTemplate');
                var next = rows.children.length;
                add.addEventListener('click', function () {
                    var html = template.innerHTML.replace(/__i__/g, String(next++));
                    rows.insertAdjacentHTML('beforeend', html);
                    rows.lastElementChild.querySelector('input').focus();
                });
            })();
        </script>
@endif
@endsection

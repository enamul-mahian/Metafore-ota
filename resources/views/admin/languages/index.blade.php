@extends('layouts.admin')

@section('title', 'Languages')

@section('content')

<x-admin.page-header title="Languages" description="Manage the languages available across the platform." icon="L" eyebrow="Master data">
    <span class="admin-status-badge">{{ $languages->count() }} total</span>
</x-admin.page-header>

<div class="egh-card">

    <div id="lng-message" class="lng-message"></div>

    @can('master-data.manage')
    <form id="language-form" class="lng-form" data-url="{{ route('admin.master-data.languages.store') }}">
        <div class="lng-two">
            <input name="name" placeholder="Language name" required maxlength="150">
            <input name="code" placeholder="ISO code e.g. en" required minlength="2" maxlength="2" pattern="[a-z]{2}">
        </div>
        <div class="lng-two">
            <input name="native_name" placeholder="Native name (optional)" maxlength="150">
            <input type="number" name="sort_order" value="0" min="0" max="4294967295" required>
        </div>
        <label><input type="checkbox" name="is_active" checked> Active</label>
        <button class="egh-button" type="submit">Add Language</button>
    </form>
    @endcan

    <div class="lng-table-wrap">
        <table class="lng-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Native name</th>
                <th>Order</th>
                <th>Status</th>
                @can('master-data.manage')<th>Actions</th>@endcan
            </tr>
            </thead>
            <tbody>
            @forelse($languages as $language)
                <tr>
                    <td>{{ $language->name }}</td>
                    <td>{{ $language->code }}</td>
                    <td>{{ $language->native_name ?: '—' }}</td>
                    <td>{{ $language->sort_order }}</td>
                    <td class="lng-status">{{ $language->is_active ? 'Active' : 'Inactive' }}</td>
                    @can('master-data.manage')
                    <td>
                        <div class="lng-actions">
                            <button type="button" class="lng-edit" data-url="{{ route('admin.master-data.languages.show', $language) }}">Edit</button>
                            <button type="button" class="lng-delete" data-name="{{ $language->name }}" data-url="{{ route('admin.master-data.languages.destroy', $language) }}">Delete</button>
                        </div>
                    </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="6">No languages found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('master-data.manage')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const box = document.getElementById('lng-message');
    const form = document.getElementById('language-form');
    const show = text => {
        box.style.display = 'block';
        box.style.background = '#fee2e2';
        box.style.color = '#991b1b';
        box.textContent = text;
    };
    const errorText = data => data?.errors
        ? Object.values(data.errors).flat().join(' ')
        : (data?.message || 'Request failed.');
    const request = async (url, method, data = null) => {
        const options = {method, headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}};
        if (data !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
        const response = await fetch(url, options);
        const body = await response.json();
        if (!response.ok) throw new Error(errorText(body));
        return body;
    };

    form.querySelector('[name="code"]').addEventListener('input', event => {
        event.target.value = event.target.value.toLowerCase();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(form).entries());
        data.code = data.code.trim().toLowerCase();
        data.native_name = data.native_name || null;
        data.sort_order = Number(data.sort_order);
        data.is_active = form.querySelector('[name="is_active"]').checked;
        try {
            await request(form.dataset.url, 'POST', data);
            location.reload();
        } catch (error) { show(error.message); }
    });

    document.querySelectorAll('.lng-edit').forEach(button => {
        button.addEventListener('click', async () => {
            try {
                const current = (await request(button.dataset.url, 'GET')).data;
                const name = prompt('Language name', current.name);
                if (name === null) return;
                const code = prompt('ISO code', current.code);
                if (code === null) return;
                const nativeName = prompt('Native name (optional)', current.native_name || '');
                if (nativeName === null) return;
                const sortOrder = prompt('Sort order', current.sort_order);
                if (sortOrder === null) return;
                const active = prompt('Active? Enter yes or no', current.is_active ? 'yes' : 'no');
                if (active === null) return;
                await request(button.dataset.url, 'PATCH', {
                    name,
                    code: code.trim().toLowerCase(),
                    native_name: nativeName || null,
                    sort_order: Number(sortOrder),
                    is_active: active.trim().toLowerCase() === 'yes',
                });
                location.reload();
            } catch (error) { show(error.message); }
        });
    });

    document.querySelectorAll('.lng-delete').forEach(button => {
        button.addEventListener('click', async () => {
            if (!confirm(`Delete ${button.dataset.name}?`)) return;
            try {
                await request(button.dataset.url, 'DELETE');
                location.reload();
            } catch (error) { show(error.message); }
        });
    });
})();
</script>
@endcan
@endsection

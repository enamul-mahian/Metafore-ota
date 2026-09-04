@extends('layouts.admin')

@section('title', 'Languages')

@section('content')
<style>
.lng-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.lng-muted{color:#64748b}.lng-form{display:grid;gap:10px;margin:18px 0}.lng-two{display:grid;grid-template-columns:1fr 1fr;gap:10px}.lng-form input{width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:8px;padding:10px;background:#fff}.lng-table-wrap{overflow:auto}.lng-table{width:100%;border-collapse:collapse;font-size:14px}.lng-table th,.lng-table td{padding:10px 8px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}.lng-table th{font-size:12px;text-transform:uppercase;color:#64748b;white-space:nowrap}.lng-actions{display:flex;gap:6px}.lng-edit,.lng-delete{border:0;border-radius:7px;padding:7px 10px;cursor:pointer}.lng-edit{background:#e9eef7;color:#273852}.lng-delete{background:#fee2e2;color:#991b1b}.lng-message{display:none;margin-bottom:16px;padding:12px 14px;border-radius:8px}.lng-status{white-space:nowrap}@media(max-width:620px){.lng-head{display:block}.lng-two{grid-template-columns:1fr}}
</style>

<div class="egh-card">
    <div class="lng-head">
        <div>
            <h1 style="margin:0">Languages</h1>
            <p class="lng-muted" style="margin:6px 0 0">Manage the languages available across the platform.</p>
        </div>
        <span class="lng-muted">{{ $languages->count() }} total</span>
    </div>

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

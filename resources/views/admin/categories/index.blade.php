@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<style>
.cat-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.cat-muted{color:#64748b}.cat-form{display:grid;gap:10px;margin:18px 0}.cat-two{display:grid;grid-template-columns:1fr 1fr;gap:10px}.cat-form input,.cat-form textarea{width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:8px;padding:10px;background:#fff}.cat-form textarea{min-height:90px;resize:vertical}.cat-table-wrap{overflow:auto}.cat-table{width:100%;border-collapse:collapse;font-size:14px}.cat-table th,.cat-table td{padding:10px 8px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}.cat-table th{font-size:12px;text-transform:uppercase;color:#64748b;white-space:nowrap}.cat-actions{display:flex;gap:6px}.cat-edit,.cat-delete{border:0;border-radius:7px;padding:7px 10px;cursor:pointer}.cat-edit{background:#e9eef7;color:#273852}.cat-delete{background:#fee2e2;color:#991b1b}.cat-message{display:none;margin-bottom:16px;padding:12px 14px;border-radius:8px}.cat-status{white-space:nowrap}@media(max-width:620px){.cat-head{display:block}.cat-two{grid-template-columns:1fr}}
</style>

<div class="egh-card">
    <div class="cat-head">
        <div>
            <h1 style="margin:0">Categories</h1>
            <p class="cat-muted" style="margin:6px 0 0">Manage reusable travel and service categories.</p>
        </div>
        <span class="cat-muted">{{ $categories->count() }} total</span>
    </div>

    <div id="cat-message" class="cat-message"></div>

    @can('master-data.manage')
    <form id="category-form" class="cat-form" data-url="{{ route('admin.master-data.categories.store') }}">
        <div class="cat-two">
            <input name="name" placeholder="Category name" required maxlength="150">
            <input name="slug" placeholder="Slug e.g. flights" required maxlength="180">
        </div>
        <textarea name="description" placeholder="Description (optional)" maxlength="5000"></textarea>
        <div class="cat-two">
            <input type="number" name="sort_order" value="0" min="0" max="4294967295" required>
            <label><input type="checkbox" name="is_active" checked> Active</label>
        </div>
        <button class="egh-button" type="submit">Add Category</button>
    </form>
    @endcan

    <div class="cat-table-wrap">
        <table class="cat-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Order</th>
                <th>Status</th>
                @can('master-data.manage')<th>Actions</th>@endcan
            </tr>
            </thead>
            <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->slug }}</td>
                    <td>{{ $category->description ?: '—' }}</td>
                    <td>{{ $category->sort_order }}</td>
                    <td class="cat-status">{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
                    @can('master-data.manage')
                    <td>
                        <div class="cat-actions">
                            <button type="button" class="cat-edit" data-url="{{ route('admin.master-data.categories.show', $category) }}">Edit</button>
                            <button type="button" class="cat-delete" data-name="{{ $category->name }}" data-url="{{ route('admin.master-data.categories.destroy', $category) }}">Delete</button>
                        </div>
                    </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="6">No categories found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('master-data.manage')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const box = document.getElementById('cat-message');
    const form = document.getElementById('category-form');

    const show = (text, ok = false) => {
        box.style.display = 'block';
        box.style.background = ok ? '#e9f8ef' : '#fee2e2';
        box.style.color = ok ? '#22643b' : '#991b1b';
        box.textContent = text;
    };

    const errorText = data => data?.errors
        ? Object.values(data.errors).flat().join(' ')
        : (data?.message || 'Request failed.');

    const request = async (url, method, data = null) => {
        const options = {
            method,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
        };

        if (data !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        const body = await response.json();

        if (!response.ok) {
            throw new Error(errorText(body));
        }

        return body;
    };

    form.addEventListener('submit', async event => {
        event.preventDefault();

        const data = Object.fromEntries(new FormData(form).entries());
        data.description = data.description || null;
        data.sort_order = Number(data.sort_order);
        data.is_active = form.querySelector('[name="is_active"]').checked;

        try {
            await request(form.dataset.url, 'POST', data);
            location.reload();
        } catch (error) {
            show(error.message);
        }
    });

    document.querySelectorAll('.cat-edit').forEach(button => {
        button.addEventListener('click', async () => {
            try {
                const current = (await request(button.dataset.url, 'GET')).data;
                const name = prompt('Category name', current.name);
                if (name === null) return;
                const slug = prompt('Slug', current.slug);
                if (slug === null) return;
                const description = prompt('Description (optional)', current.description || '');
                if (description === null) return;
                const sortOrder = prompt('Sort order', current.sort_order);
                if (sortOrder === null) return;
                const active = prompt('Active? Enter yes or no', current.is_active ? 'yes' : 'no');
                if (active === null) return;

                await request(button.dataset.url, 'PATCH', {
                    name,
                    slug,
                    description: description || null,
                    sort_order: Number(sortOrder),
                    is_active: active.trim().toLowerCase() === 'yes',
                });

                location.reload();
            } catch (error) {
                show(error.message);
            }
        });
    });

    document.querySelectorAll('.cat-delete').forEach(button => {
        button.addEventListener('click', async () => {
            if (!confirm(`Delete ${button.dataset.name}?`)) return;

            try {
                await request(button.dataset.url, 'DELETE');
                location.reload();
            } catch (error) {
                show(error.message);
            }
        });
    });
})();
</script>
@endcan
@endsection

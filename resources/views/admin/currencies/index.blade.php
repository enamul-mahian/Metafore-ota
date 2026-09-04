@extends('layouts.admin')

@section('title', 'Currencies')

@section('content')
<style>
.cur-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.cur-muted{color:#64748b}.cur-form{display:grid;gap:10px;margin:18px 0}.cur-two{display:grid;grid-template-columns:1fr 1fr;gap:10px}.cur-form input{width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:8px;padding:10px;background:#fff}.cur-table-wrap{overflow:auto}.cur-table{width:100%;border-collapse:collapse;font-size:14px}.cur-table th,.cur-table td{padding:10px 8px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}.cur-table th{font-size:12px;text-transform:uppercase;color:#64748b;white-space:nowrap}.cur-actions{display:flex;gap:6px}.cur-edit,.cur-delete{border:0;border-radius:7px;padding:7px 10px;cursor:pointer}.cur-edit{background:#e9eef7;color:#273852}.cur-delete{background:#fee2e2;color:#991b1b}.cur-message{display:none;margin-bottom:16px;padding:12px 14px;border-radius:8px}.cur-status{white-space:nowrap}@media(max-width:620px){.cur-head{display:block}.cur-two{grid-template-columns:1fr}}
</style>

<div class="egh-card">
    <div class="cur-head">
        <div>
            <h1 style="margin:0">Currencies</h1>
            <p class="cur-muted" style="margin:6px 0 0">Manage the currencies available across the platform.</p>
        </div>
        <span class="cur-muted">{{ $currencies->count() }} total</span>
    </div>

    <div id="cur-message" class="cur-message"></div>

    @can('master-data.manage')
    <form id="currency-form" class="cur-form" data-url="{{ route('admin.master-data.currencies.store') }}">
        <div class="cur-two">
            <input name="name" placeholder="Currency name" required maxlength="150">
            <input name="code" placeholder="ISO code e.g. USD" required minlength="3" maxlength="3" pattern="[A-Z]{3}">
        </div>
        <div class="cur-two">
            <input name="symbol" placeholder="Symbol (optional)" maxlength="16">
            <input type="number" name="decimal_places" value="2" min="0" max="4" required>
        </div>
        <div class="cur-two">
            <input type="number" name="sort_order" value="0" min="0" max="4294967295" required>
            <label><input type="checkbox" name="is_active" checked> Active</label>
        </div>
        <button class="egh-button" type="submit">Add Currency</button>
    </form>
    @endcan

    <div class="cur-table-wrap">
        <table class="cur-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Symbol</th>
                <th>Decimals</th>
                <th>Order</th>
                <th>Status</th>
                @can('master-data.manage')<th>Actions</th>@endcan
            </tr>
            </thead>
            <tbody>
            @forelse($currencies as $currency)
                <tr>
                    <td>{{ $currency->name }}</td>
                    <td>{{ $currency->code }}</td>
                    <td>{{ $currency->symbol ?: '—' }}</td>
                    <td>{{ $currency->decimal_places }}</td>
                    <td>{{ $currency->sort_order }}</td>
                    <td class="cur-status">{{ $currency->is_active ? 'Active' : 'Inactive' }}</td>
                    @can('master-data.manage')
                    <td>
                        <div class="cur-actions">
                            <button type="button" class="cur-edit" data-url="{{ route('admin.master-data.currencies.show', $currency) }}">Edit</button>
                            <button type="button" class="cur-delete" data-name="{{ $currency->name }}" data-url="{{ route('admin.master-data.currencies.destroy', $currency) }}">Delete</button>
                        </div>
                    </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="7">No currencies found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('master-data.manage')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const box = document.getElementById('cur-message');
    const form = document.getElementById('currency-form');

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

    form.querySelector('[name="code"]').addEventListener('input', event => {
        event.target.value = event.target.value.toUpperCase();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        const data = Object.fromEntries(new FormData(form).entries());
        data.code = data.code.trim().toUpperCase();
        data.symbol = data.symbol || null;
        data.decimal_places = Number(data.decimal_places);
        data.sort_order = Number(data.sort_order);
        data.is_active = form.querySelector('[name="is_active"]').checked;

        try {
            await request(form.dataset.url, 'POST', data);
            location.reload();
        } catch (error) {
            show(error.message);
        }
    });

    document.querySelectorAll('.cur-edit').forEach(button => {
        button.addEventListener('click', async () => {
            try {
                const current = (await request(button.dataset.url, 'GET')).data;
                const name = prompt('Currency name', current.name);
                if (name === null) return;
                const code = prompt('ISO code', current.code);
                if (code === null) return;
                const symbol = prompt('Symbol (optional)', current.symbol || '');
                if (symbol === null) return;
                const decimalPlaces = prompt('Decimal places (0-4)', current.decimal_places);
                if (decimalPlaces === null) return;
                const sortOrder = prompt('Sort order', current.sort_order);
                if (sortOrder === null) return;
                const active = prompt('Active? Enter yes or no', current.is_active ? 'yes' : 'no');
                if (active === null) return;

                await request(button.dataset.url, 'PATCH', {
                    name,
                    code: code.trim().toUpperCase(),
                    symbol: symbol || null,
                    decimal_places: Number(decimalPlaces),
                    sort_order: Number(sortOrder),
                    is_active: active.trim().toLowerCase() === 'yes',
                });

                location.reload();
            } catch (error) {
                show(error.message);
            }
        });
    });

    document.querySelectorAll('.cur-delete').forEach(button => {
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

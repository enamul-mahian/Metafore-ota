@extends('layouts.admin')

@section('title', 'Master Data')

@section('content')

<x-admin.page-header title="Countries & Cities" description="Manage location records used across travel services." icon="M" eyebrow="Master data" />

<div class="egh-card">

    <div id="md-message" class="md-message"></div>

    <div class="md-grid">
        <section class="md-section">
            <div class="md-title">
                <h2>Countries</h2>
                <span class="md-muted">{{ $countries->count() }} total</span>
            </div>

            @can('master-data.manage')
            <form id="country-form" class="md-form" data-url="{{ route('admin.master-data.countries.store') }}">
                <input name="name" placeholder="Country name" required maxlength="150">
                <div class="md-two">
                    <input name="iso2" placeholder="ISO2 e.g. BD" required maxlength="2">
                    <input name="iso3" placeholder="ISO3 e.g. BGD" required maxlength="3">
                </div>
                <input name="phone_code" placeholder="Phone code e.g. +880" maxlength="10">
                <label><input type="checkbox" name="is_active" checked> Active</label>
                <button class="egh-button" type="submit">Add Country</button>
            </form>
            @endcan

            <div class="md-table-wrap">
                <table class="md-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>ISO</th>
                        <th>Phone</th>
                        <th>Status</th>
                        @can('master-data.manage')<th>Actions</th>@endcan
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($countries as $country)
                        <tr>
                            <td>{{ $country->name }}</td>
                            <td>{{ $country->iso2 }} / {{ $country->iso3 }}</td>
                            <td>{{ $country->phone_code ?: '—' }}</td>
                            <td>{{ $country->is_active ? 'Active' : 'Inactive' }}</td>
                            @can('master-data.manage')
                            <td>
                                <div class="md-actions">
                                    <button type="button" class="md-edit" data-kind="country" data-url="{{ route('admin.master-data.countries.show', $country) }}">Edit</button>
                                    <button type="button" class="md-delete" data-name="{{ $country->name }}" data-url="{{ route('admin.master-data.countries.destroy', $country) }}">Delete</button>
                                </div>
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="5">No countries found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="md-section">
            <div class="md-title">
                <h2>Cities</h2>
                <span class="md-muted">{{ $cities->count() }} total</span>
            </div>

            @can('master-data.manage')
            <form id="city-form" class="md-form" data-url="{{ route('admin.master-data.cities.store') }}">
                <select name="country_id" required>
                    <option value="">Select country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }} ({{ $country->iso2 }})</option>
                    @endforeach
                </select>
                <input name="name" placeholder="City name" required maxlength="150">
                <div class="md-two">
                    <input name="code" placeholder="Code e.g. DAC" maxlength="3">
                    <input name="timezone" placeholder="Timezone e.g. Asia/Dhaka" maxlength="64">
                </div>
                <label><input type="checkbox" name="is_active" checked> Active</label>
                <button class="egh-button" type="submit">Add City</button>
            </form>
            @endcan

            <div class="md-table-wrap">
                <table class="md-table">
                    <thead>
                    <tr>
                        <th>City</th>
                        <th>Country</th>
                        <th>Code</th>
                        <th>Timezone</th>
                        <th>Status</th>
                        @can('master-data.manage')<th>Actions</th>@endcan
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($cities as $city)
                        <tr>
                            <td>{{ $city->name }}</td>
                            <td>{{ $city->country?->name ?? '—' }}</td>
                            <td>{{ $city->code ?: '—' }}</td>
                            <td>{{ $city->timezone ?: '—' }}</td>
                            <td>{{ $city->is_active ? 'Active' : 'Inactive' }}</td>
                            @can('master-data.manage')
                            <td>
                                <div class="md-actions">
                                    <button type="button" class="md-edit" data-kind="city" data-url="{{ route('admin.master-data.cities.show', $city) }}">Edit</button>
                                    <button type="button" class="md-delete" data-name="{{ $city->name }}" data-url="{{ route('admin.master-data.cities.destroy', $city) }}">Delete</button>
                                </div>
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="6">No cities found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

@can('master-data.manage')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const box = document.getElementById('md-message');

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

    for (const id of ['country-form', 'city-form']) {
        const form = document.getElementById(id);
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const data = Object.fromEntries(new FormData(form).entries());
            data.is_active = form.querySelector('[name="is_active"]').checked;

            for (const key of ['iso2', 'iso3', 'code']) {
                if (data[key]) data[key] = data[key].toUpperCase();
            }

            try {
                await request(form.dataset.url, 'POST', data);
                location.reload();
            } catch (error) {
                show(error.message);
            }
        });
    }

    document.querySelectorAll('.md-edit').forEach(button => {
        button.addEventListener('click', async () => {
            try {
                const current = (await request(button.dataset.url, 'GET')).data;
                let data;

                if (button.dataset.kind === 'country') {
                    const name = prompt('Country name', current.name);
                    if (name === null) return;
                    const iso2 = prompt('ISO2', current.iso2);
                    if (iso2 === null) return;
                    const iso3 = prompt('ISO3', current.iso3);
                    if (iso3 === null) return;
                    const phoneCode = prompt('Phone code (optional)', current.phone_code || '');
                    if (phoneCode === null) return;
                    const active = prompt('Active? Enter yes or no', current.is_active ? 'yes' : 'no');
                    if (active === null) return;

                    data = {
                        name,
                        iso2: iso2.toUpperCase(),
                        iso3: iso3.toUpperCase(),
                        phone_code: phoneCode || null,
                        is_active: active.trim().toLowerCase() === 'yes',
                    };
                } else {
                    const countryId = prompt('Country ID', current.country_id);
                    if (countryId === null) return;
                    const name = prompt('City name', current.name);
                    if (name === null) return;
                    const code = prompt('City code (optional)', current.code || '');
                    if (code === null) return;
                    const timezone = prompt('Timezone (optional)', current.timezone || '');
                    if (timezone === null) return;
                    const active = prompt('Active? Enter yes or no', current.is_active ? 'yes' : 'no');
                    if (active === null) return;

                    data = {
                        country_id: Number(countryId),
                        name,
                        code: code ? code.toUpperCase() : null,
                        timezone: timezone || null,
                        is_active: active.trim().toLowerCase() === 'yes',
                    };
                }

                await request(button.dataset.url, 'PATCH', data);
                location.reload();
            } catch (error) {
                show(error.message);
            }
        });
    });

    document.querySelectorAll('.md-delete').forEach(button => {
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

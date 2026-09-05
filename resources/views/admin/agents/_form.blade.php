@if($errors->any())
    <div class="egh-alert is-error">
        <ul class="admin-error-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="agt-form-grid">
    <label>Name<input name="name" value="{{ old('name', $agent->name ?? '') }}" required maxlength="150"></label>
    <label>Email<input type="email" name="email" value="{{ old('email', $agent->email ?? '') }}" required maxlength="255"></label>
    <label>Phone<input type="tel" name="phone" value="{{ old('phone', $agent->phone ?? '') }}" maxlength="32"></label>
    <label>Company<input name="company_name" value="{{ old('company_name', $agent->company_name ?? '') }}" maxlength="150"></label>
    <label>Registration number<input name="registration_number" value="{{ old('registration_number', $agent->registration_number ?? '') }}" maxlength="100"></label>
    <label>Country
        <select name="country_id">
            <option value="">Not specified</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}" @selected((string) old('country_id', $agent->country_id ?? '') === (string) $country->id)>{{ $country->name }} ({{ $country->iso2 }})</option>
            @endforeach
        </select>
    </label>
    <label>Status
        <select name="status" required>
            @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $agent->status ?? \App\Models\Agent::STATUS_PENDING) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </label>
    <label class="agt-full">Internal notes<textarea name="notes" maxlength="5000">{{ old('notes', $agent->notes ?? '') }}</textarea></label>
</div>

<div class="admin-form-actions">
    <button class="egh-button" type="submit">{{ $submitLabel }}</button>
    <a class="egh-button secondary" href="{{ route('admin.agents.index') }}">Cancel</a>
</div>

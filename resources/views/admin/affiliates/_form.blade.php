@if($errors->any())<div class="egh-alert is-error"><ul class="admin-error-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="af-form-grid">
<label>Name<input name="name" value="{{ old('name', $affiliate->name ?? '') }}" required maxlength="150"></label>
<label>Email<input type="email" name="email" value="{{ old('email', $affiliate->email ?? '') }}" required maxlength="255"></label>
<label>Phone<input type="tel" name="phone" value="{{ old('phone', $affiliate->phone ?? '') }}" maxlength="32"></label>
<label>Organization<input name="organization_name" value="{{ old('organization_name', $affiliate->organization_name ?? '') }}" maxlength="150"></label>
<label>Referral code<input name="referral_code" value="{{ old('referral_code', $affiliate->referral_code ?? '') }}" required maxlength="64"></label>
<label>Website<input type="url" name="website_url" value="{{ old('website_url', $affiliate->website_url ?? '') }}" maxlength="2048" placeholder="https://example.com"></label>
<label>Country<select name="country_id"><option value="">Not specified</option>@foreach($countries as $country)<option value="{{ $country->id }}" @selected((string) old('country_id', $affiliate->country_id ?? '') === (string) $country->id)>{{ $country->name }} ({{ $country->iso2 }})</option>@endforeach</select></label>
<label>Status<select name="status" required>@foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status', $affiliate->status ?? \App\Models\Affiliate::STATUS_PENDING) === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
<label class="af-full">Internal notes<textarea name="notes" maxlength="5000">{{ old('notes', $affiliate->notes ?? '') }}</textarea></label>
</div><div class="admin-form-actions"><button class="egh-button">{{ $submitLabel }}</button><a class="egh-button secondary" href="{{ route('admin.affiliates.index') }}">Cancel</a></div>

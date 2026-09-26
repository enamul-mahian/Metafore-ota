@if($errors->any())
    <div class="egh-alert is-error">
        <ul class="admin-error-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="agt-form-grid">
    <label>Country
        <select name="country_id" required>
            <option value="">Select country</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}" @selected((string) old('country_id', $destination->country_id ?? '') === (string) $country->id)>{{ $country->name }} ({{ $country->iso2 }})</option>
            @endforeach
        </select>
    </label>
    <label>URL slug<input name="slug" value="{{ old('slug', $destination->slug ?? '') }}" required maxlength="120" placeholder="e.g. canada" pattern="[a-z0-9\-]+"></label>
    <label>Visa type<input name="visa_type" value="{{ old('visa_type', $destination->visa_type ?? '') }}" maxlength="150" placeholder="e.g. Skilled Worker Visa"></label>
    <label>Processing time<input name="processing_time" value="{{ old('processing_time', $destination->processing_time ?? '') }}" maxlength="100" placeholder="e.g. 8–12 weeks"></label>
    <label>Service fee (leave empty to hide)<input type="number" name="service_fee" value="{{ old('service_fee', $destination->service_fee ?? '') }}" min="0" step="0.01" inputmode="decimal"></label>
    <label>Currency
        <select name="currency" required>
            @foreach($currencies as $currency)
                <option value="{{ $currency }}" @selected(old('currency', $destination->currency ?? 'BDT') === $currency)>{{ $currency }}</option>
            @endforeach
        </select>
    </label>
    <label>Sort order<input type="number" name="sort_order" value="{{ old('sort_order', $destination->sort_order ?? 0) }}" min="0" step="1" inputmode="numeric"></label>
    <label style="flex-direction:row;align-items:center;gap:10px"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" style="width:18px;height:18px;min-height:0" @checked(old('is_active', $destination->is_active ?? true))> Active (shown on the website)</label>
    <label class="agt-full">Short summary<textarea name="summary" maxlength="500">{{ old('summary', $destination->summary ?? '') }}</textarea></label>
    <label class="agt-full">Job categories (one per line)<textarea name="job_categories" rows="6">{{ collect(old('job_categories', $destination->jobCategoryList()))->implode("\n") }}</textarea></label>
    <label class="agt-full">Required documents (one per line)<textarea name="requirements" rows="6">{{ collect(old('requirements', $destination->requirementList()))->implode("\n") }}</textarea></label>
</div>

<div class="admin-form-actions">
    <button class="egh-button" type="submit">{{ $submitLabel }}</button>
    <a class="egh-button secondary" href="{{ route('admin.work-visa.destinations.index') }}">Cancel</a>
</div>

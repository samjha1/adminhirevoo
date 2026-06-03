{{-- Shown when stage is follow_up or interview (candidates). Field names match backend validation. --}}
<div class="crm-follow-up-stage-fields border rounded-3 p-3 bg-light d-none" data-follow-up-fields>
    <div class="small fw-semibold text-dark mb-2">
        <i class="bi bi-calendar-event me-1"></i>Schedule details
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold mb-1">Date &amp; time <span class="text-danger">*</span></label>
        <input type="datetime-local" name="follow_up_scheduled_at" class="form-control form-control-sm"
               value="{{ old('follow_up_scheduled_at') }}">
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold mb-1">Notes</label>
        <textarea name="follow_up_notes" class="form-control form-control-sm" rows="2"
                  placeholder="What to discuss on the next touch…">{{ old('follow_up_notes') }}</textarea>
    </div>
</div>

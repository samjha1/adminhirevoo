{{-- Shown when pipeline stage is set to "meeting_scheduled". --}}
<div class="crm-meeting-stage-fields border rounded-3 p-3 bg-light d-none" data-meeting-fields>
    <div class="small fw-semibold text-dark mb-2">
        <i class="bi bi-camera-video me-1"></i>Meeting details
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold mb-1">Meeting date &amp; time <span class="text-danger">*</span></label>
        <input type="datetime-local" name="meeting_scheduled_at" class="form-control form-control-sm"
               value="{{ old('meeting_scheduled_at') }}">
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold mb-1">Notes</label>
        <textarea name="meeting_notes" class="form-control form-control-sm" rows="2"
                  placeholder="Agenda, attendees, meeting link…">{{ old('meeting_notes') }}</textarea>
    </div>
</div>

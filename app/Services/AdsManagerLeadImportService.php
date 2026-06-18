<?php

namespace App\Services;

use App\Models\Leadsmanager\LeadsmanagerAdvertiser;
use App\Models\Leadsmanager\LeadsmanagerCampaign;
use App\Models\Leadsmanager\LeadsmanagerLeadFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AdsManagerLeadImportService
{
    /** @return array{imported: int, skipped: int} */
    public function importCsv(UploadedFile $file, int $advertiserId, ?int $campaignId = null): array
    {
        abort_unless(Schema::hasTable('leadsmanager_lead_files'), 503, 'Ads Manager lead file table is not available. Run leadsmanager migrations.');

        if ($campaignId) {
            $campaign = LeadsmanagerCampaign::query()->findOrFail($campaignId);
            $advertiserId = (int) $campaign->user_id;
        }

        LeadsmanagerAdvertiser::query()->where('id', $advertiserId)->where('role', 'user')->firstOrFail();

        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        if (! in_array($extension, ['csv', 'txt', 'xlsx'], true)) {
            throw new RuntimeException('Only CSV or Excel (.xlsx) files are allowed.');
        }

        $format = $extension === 'xlsx' ? 'xlsx' : 'csv';
        $directory = 'leadsmanager/leads/'.date('Y/m');
        $disk = $this->uploadsDisk();

        try {
            $path = Storage::disk($disk)->putFile($directory, $file);
        } catch (Throwable $e) {
            Log::error('Ads Manager lead file upload failed', [
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Upload failed: '.$e->getMessage());
        }

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Upload failed. Check AWS S3 credentials and bucket permissions.');
        }

        LeadsmanagerLeadFile::query()->create([
            'user_id' => $advertiserId,
            'campaign_id' => $campaignId,
            // Adminpanal users live in a separate table; uploaded_by FK targets leadsmanager_users only.
            'uploaded_by' => null,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => str_replace('\\', '/', $path),
            'format' => $format,
            'file_size' => (int) $file->getSize(),
        ]);

        return ['imported' => 1, 'skipped' => 0];
    }

    public function assignLeads(array $fileIds, int $campaignId): int
    {
        $campaign = LeadsmanagerCampaign::query()->findOrFail($campaignId);
        $count = 0;

        foreach ($fileIds as $fileId) {
            $updated = LeadsmanagerLeadFile::query()
                ->where('id', $fileId)
                ->update([
                    'campaign_id' => $campaign->id,
                    'user_id' => $campaign->user_id,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                $count++;
            }
        }

        return $count;
    }

    private function uploadsDisk(): string
    {
        $bucket = (string) config('filesystems.disks.s3.bucket');
        $key = (string) config('filesystems.disks.s3.key');
        $secret = (string) config('filesystems.disks.s3.secret');

        if ($bucket !== '' && $key !== '' && $secret !== '') {
            return 's3';
        }

        return (string) config('filesystems.default', 'local');
    }
}

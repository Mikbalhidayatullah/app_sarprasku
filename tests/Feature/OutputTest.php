<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Role;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Support\InventoryFixture;
use Tests\TestCase;

class OutputTest extends TestCase
{
    use InventoryFixture, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventorySetUp();
    }

    public function test_qr_requires_login_and_returns_to_asset_with_stable_token(): void
    {
        $a = $this->asset();
        $token = $a->qr_token;
        $this->get('/q/'.$token)->assertRedirect('/login');
        $this->actingAs($this->operator)->get('/q/'.$token)->assertRedirect('/assets/'.$a->id);
        $this->assertSame($token, $a->fresh()->qr_token);
        $this->get('/q/not-a-real-token')->assertNotFound();
    }

    public function test_object_attachment_qr_and_export_are_forbidden_without_permissions(): void
    {
        $a = $this->asset();
        $user = $this->makeUser('viewer');
        $role = Role::find($user->role_id);
        $role->permissions()->detach();
        $attachment = Attachment::create(['asset_id' => $a->id, 'path' => 'private.pdf', 'original_name' => 'private.pdf', 'mime' => 'application/pdf', 'size' => 10, 'uploaded_by' => $this->operator->id]);
        $this->actingAs($user)->get('/assets/'.$a->id)->assertForbidden();
        $this->get('/q/'.$a->qr_token)->assertForbidden();
        $this->get('/attachments/'.$attachment->id)->assertForbidden();
        $this->get('/reports/export')->assertForbidden();
        $this->get('/labels?ids[]='.$a->id)->assertForbidden();
    }

    public function test_upload_rejects_svg_and_html_and_stores_valid_files_privately(): void
    {
        Storage::fake('local');
        $a = $this->asset();
        $this->actingAs($this->operator)->post('/assets/'.$a->id.'/attachments', ['file' => UploadedFile::fake()->createWithContent('evil.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')])->assertSessionHasErrors('file');
        $this->post('/assets/'.$a->id.'/attachments', ['file' => UploadedFile::fake()->createWithContent('evil.html', '<html>hello</html>')])->assertSessionHasErrors('file');
        $this->post('/assets/'.$a->id.'/attachments', ['file' => UploadedFile::fake()->image('photo.png')])->assertSessionHasNoErrors();
        $file = Attachment::firstOrFail();
        Storage::disk('local')->assertExists($file->path);
        $this->assertStringNotContainsString('photo.png', $file->path);
        $this->get('/attachments/'.$file->id)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/storage/'.$file->path)->assertNotFound();
    }

    public function test_report_csv_filters_and_neutralizes_formula_injection(): void
    {
        $this->asset(['name' => '=HYPERLINK("bad")', 'room_id' => $this->origin->id, 'inventory_number' => '0007']);
        $this->asset(['name' => 'Excluded', 'room_id' => $this->destination->id]);
        $response = $this->actingAs($this->operator)->get('/reports/export?room_id='.$this->origin->id)->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'0007", $csv);
        $this->assertStringNotContainsString('Excluded', $csv);
    }

    public function test_label_preview_and_pdf_use_local_qr_and_report_pdf_is_valid(): void
    {
        $a = $this->asset(['name' => str_repeat('Nama panjang ', 15)]);
        $this->actingAs($this->operator)->get('/labels?ids[]='.$a->id)->assertOk()->assertSee('data:image/png;base64', false)->assertSee('Pratinjau');
        $pdf = $this->get('/labels?format=pdf&ids[]='.$a->id)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $pdf->getContent()), 'Satu label harus menghasilkan satu halaman A4 tanpa halaman kosong.');
        File::ensureDirectoryExists(storage_path('qa'));
        file_put_contents(storage_path('qa/labels-test.pdf'), $pdf->getContent());
        $report = $this->get('/reports/pdf')->assertOk();
        $this->assertStringStartsWith('%PDF-', $report->getContent());
        file_put_contents(storage_path('qa/report-test.pdf'), $report->getContent());
    }

    public function test_twelve_labels_fit_one_a4_and_thirteen_use_two_pages(): void
    {
        $ids = [];
        for ($i = 1; $i <= 12; $i++) {
            $ids[] = $this->asset(['inventory_number' => 'LABEL-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'name' => 'Nama barang untuk label '.$i])->id;
        }
        $this->actingAs($this->operator);
        $pdf = $this->get('/labels?'.http_build_query(['format' => 'pdf', 'ids' => $ids]))->assertOk();
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $pdf->getContent()));
        file_put_contents(storage_path('qa/labels-batch-test.pdf'), $pdf->getContent());
        $ids[] = $this->asset(['inventory_number' => 'LABEL-0013'])->id;
        $pdf = $this->get('/labels?'.http_build_query(['format' => 'pdf', 'ids' => $ids]))->assertOk();
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $pdf->getContent()));
    }

    public function test_condition_and_maintenance_append_history_and_update_current_condition(): void
    {
        $a = $this->asset();
        $this->actingAs($this->operator)->post('/assets/'.$a->id.'/condition', ['condition' => 'light_damage', 'inspected_on' => School::today(), 'inspector' => 'Pemeriksa sekolah', 'reason' => 'Kaki patah'])->assertSessionHasNoErrors();
        $this->assertSame('light_damage', $a->fresh()->condition);
        $this->post('/maintenances', ['asset_id' => $a->id, 'performed_on' => School::today(), 'complaint' => 'Kaki patah', 'action' => 'Penggantian', 'vendor' => 'Bengkel', 'cost' => '100000.50', 'result' => 'Sudah dapat dipakai', 'condition_after' => 'good'])->assertSessionHasNoErrors();
        $this->assertSame('good', $a->fresh()->condition);
        $this->assertDatabaseCount('maintenances', 1);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $a->id, 'type' => 'condition.changed']);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $a->id, 'type' => 'maintenance.recorded']);
    }

    public function test_archive_is_not_disposal_and_disposal_requires_document(): void
    {
        $a = $this->asset();
        $admin = $this->makeUser('administrator');
        $this->actingAs($admin)->post('/assets/'.$a->id.'/status', ['lifecycle_status' => 'disposed', 'reason' => 'Proses internal'])->assertSessionHasErrors('disposal_reference');
        $this->post('/assets/'.$a->id.'/status', ['lifecycle_status' => 'archived', 'reason' => 'Selesai penggunaan'])->assertSessionHasNoErrors();
        $this->assertSame('archived', $a->fresh()->lifecycle_status);
        $this->assertDatabaseHas('assets', ['id' => $a->id]);
    }
}

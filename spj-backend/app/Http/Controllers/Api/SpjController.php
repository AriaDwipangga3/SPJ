<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spj;
use App\Notifications\SpjDiperbarui;
use App\Services\ActivityLogger;
use App\Services\SpjNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SpjController extends Controller
{
    public function index(Request $request)
    {
        $query = Spj::with(['jenisSpj', 'pihak1', 'pihak2', 'pembuat']);

        if (! $request->user()->isAdmin()) {
            $query->where('dibuat_oleh', $request->user()->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_spj', 'like', "%{$search}%")
                    ->orWhereHas('pihak1', fn ($p) => $p->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('pihak2', fn ($p) => $p->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($jenisSpjId = $request->input('jenis_spj_id')) {
            $query->where('jenis_spj_id', $jenisSpjId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return $query->latest('tanggal')->paginate($request->input('per_page', 10));
    }

    public function store(Request $request, SpjNumberService $numberService)
    {
        abort_if($request->user()->isAdmin(), 403, 'Admin tidak bisa membuat SPJ.');

        $data = $request->validate([
            'nomor_spj' => ['nullable', 'string', 'max:64', 'unique:spjs,nomor_spj'],
            'tanggal' => ['required', 'date'],
            'jenis_spj_id' => ['required', 'exists:jenis_spj,id'],
            'perihal' => ['nullable', 'string'],
            'uraian' => ['nullable', 'string'],
            'jumlah_anggaran' => ['nullable', 'numeric'],
        ]);

        $spj = DB::transaction(function () use ($data, $request, $numberService) {
            $tahun = (int) date('Y', strtotime($data['tanggal']));
            $kodeUnit = $request->user()->kode_unit ?? null;

            if (empty($data['nomor_spj'])) {
                $gen = $numberService->generate($tahun, $kodeUnit);
                $data['nomor_spj'] = $gen['nomor_spj'];
                $data['nomor_urut'] = $gen['nomor_urut'];
                $data['tahun_anggaran'] = $gen['tahun_anggaran'];
            } else {
                $data['tahun_anggaran'] = $tahun;
            }

            $data['dibuat_oleh'] = $request->user()->id;
            $data['status'] = 'draft';

            $spj = Spj::create($data);

            foreach ($spj->jenisSpj->dokumenWajib as $dok) {
                $spj->dokumen()->create([
                    'dokumen_wajib_id' => $dok->id,
                    'status' => 'belum',
                ]);
            }

            return $spj;
        });

        // ============ LOG ============
        ActivityLogger::log(
            'spj_create',
            'spj',
            "Buat SPJ baru: {$spj->nomor_spj}",
            $spj,
            ['nomor_spj' => $spj->nomor_spj, 'jenis_spj_id' => $spj->jenis_spj_id],
            $request
        );

        return response()->json($spj->load('dokumen.dokumenWajib'), 201);
    }

    public function show(Request $request, Spj $spj)
    {
        $this->authorizeView($request, $spj);

        return $spj->load([
            'jenisSpj',
            'pihak1',
            'pihak2',
            'pembuat',
            'verifier',
            'dokumen.dokumenWajib',
        ]);
    }

    public function update(Request $request, Spj $spj)
    {
        $this->authorizeOwnerOnly($request, $spj);
        abort_if($spj->terkunci(), 403, 'SPJ yang sudah terverifikasi tidak bisa diubah lagi. Hubungi admin.');

        $data = $request->validate([
            'tanggal' => ['sometimes', 'date'],
            'jenis_spj_id' => ['sometimes', 'exists:jenis_spj,id'],
            'perihal' => ['nullable', 'string'],
            'uraian' => ['nullable', 'string'],
            'jumlah_anggaran' => ['nullable', 'numeric'],
            'pihak1_id' => ['nullable', 'exists:responsibles,id'],
            'pihak2_id' => [
                'nullable',
                'exists:responsibles,id',
                'different:pihak1_id',
            ],
        ], [
            'pihak2_id.different' => 'Pihak I dan Pihak II tidak boleh orang yang sama.',
        ]);

        $sudahPernahDiajukan = $spj->pernahDiajukan();

        $spj->update($data);
        $adaPerubahan = $spj->wasChanged();

        // Kalau naik ke 'proses_verifikasi' -> notif pengajuan ke admin.
        // Kalau tidak, tapi data SPJ yang sudah masuk alur verifikasi berubah -> notif "diubah".
        $notifPengajuan = $spj->refresh()->refreshStatusDanNotifikasi();

        if (! $notifPengajuan && $sudahPernahDiajukan && $adaPerubahan) {
            $spj->notifyAdmins(new SpjDiperbarui($spj, 'edit'));
        }

        // ============ LOG ============
        ActivityLogger::log(
            'spj_update',
            'spj',
            "Edit SPJ: {$spj->nomor_spj}",
            $spj,
            ['nomor_spj' => $spj->nomor_spj, 'status' => $spj->status],
            $request
        );

        return response()->json($spj->fresh([
            'jenisSpj',
            'pihak1',
            'pihak2',
            'pembuat',
            'verifier',
            'dokumen.dokumenWajib',
        ]));
    }

    public function destroy(Request $request, Spj $spj)
    {
        $isAdmin = $request->user()->isAdmin();

        // Cek hak akses DULU. Percobaan yang ditolak (abort 403) berhenti di sini
        // dan tidak tercatat di log seolah-olah berhasil.
        if (! $isAdmin) {
            abort_if($spj->dibuat_oleh !== $request->user()->id, 403, 'Anda bukan pemilik SPJ ini.');
            abort_if(
                in_array($spj->status, ['terverifikasi', 'selesai'], true),
                403,
                'SPJ yang sudah terverifikasi tidak bisa dihapus sendiri. Hubungi admin.'
            );
        }

        // ============ LOG (setelah lolos cek akses, sebelum delete) ============
        ActivityLogger::log(
            'spj_delete',
            'spj',
            "Hapus SPJ: {$spj->nomor_spj}",
            $spj,
            ['nomor_spj' => $spj->nomor_spj, 'status' => $spj->status],
            $request
        );

        $spj->delete();

        return response()->json([
            'message' => $isAdmin ? 'SPJ dihapus (oleh admin).' : 'SPJ dihapus.',
        ]);
    }

    public function exportPdf(Request $request, Spj $spj)
    {
        $this->authorizeView($request, $spj);

        $spj->load([
            'jenisSpj',
            'pihak1',
            'pihak2',
            'pembuat',
            'verifier',
            'dokumen.dokumenWajib',
        ]);

        // ============ LOG ============
        ActivityLogger::log(
            'spj_pdf',
            'spj',
            "Cetak PDF SPJ: {$spj->nomor_spj}",
            $spj,
            ['nomor_spj' => $spj->nomor_spj],
            $request
        );

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.spj', [
            'spj' => $spj,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $pdf->setOption('isFontSubsettingEnabled', false);
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isPhpEnabled', false);
        $pdf->setOption('defaultFont', 'Helvetica');
        $pdf->setOption('defaultMediaType', 'print');
        $pdf->setOption('dpi', 96);

        $namaFile = 'SPJ_' . str_replace('/', '_', $spj->nomor_spj) . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($namaFile);
    }

    public function export(Request $request)
    {
        $query = Spj::with(['jenisSpj', 'pihak1', 'pihak2', 'pembuat', 'verifier']);

        if (! $request->user()->isAdmin()) {
            $query->where('dibuat_oleh', $request->user()->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_spj', 'like', "%{$search}%")
                    ->orWhere('perihal', 'like', "%{$search}%")
                    ->orWhereHas('pihak1', fn ($p) => $p->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('pihak2', fn ($p) => $p->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($jenisSpjId = $request->input('jenis_spj_id')) {
            $query->where('jenis_spj_id', $jenisSpjId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dari = $request->input('dari_tanggal')) {
            $query->whereDate('tanggal', '>=', $dari);
        }
        if ($sampai = $request->input('sampai_tanggal')) {
            $query->whereDate('tanggal', '<=', $sampai);
        }

        $rows = $query
            ->orderBy('tanggal', 'desc')
            ->orderBy('nomor_urut', 'desc')
            ->get();

        // ============ LOG ============
        ActivityLogger::log(
            'spj_export',
            'spj',
            'Ekspor data SPJ ke XLSX (' . $rows->count() . ' baris)',
            null,
            ['total' => $rows->count()],
            $request
        );

        $spreadsheet = new Spreadsheet();
        $this->buildRingkasanSheet($spreadsheet, $rows, $request);
        $this->buildDataSheet($spreadsheet, $rows);
        $spreadsheet->setActiveSheetIndex(0);

        $namaFile = 'SPJ_' . now()->format('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $namaFile . '"',
        ]);
    }

    private function buildRingkasanSheet(Spreadsheet $spreadsheet, $rows, Request $request): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan');

        $HEADER_COLOR = '1E293B';
        $ACCENT_BG = 'F1F5F9';

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'RINGKASAN SPJ');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $HEADER_COLOR]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(40);

        $periode = 'Semua Periode';
        if ($dari = $request->input('dari_tanggal')) {
            $periode = 'Dari ' . $dari;
            if ($sampai = $request->input('sampai_tanggal')) {
                $periode .= ' s/d ' . $sampai;
            }
        } elseif ($sampai = $request->input('sampai_tanggal')) {
            $periode = 'Sampai ' . $sampai;
        }

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Periode: ' . $periode . '  |  Dicetak: ' . now()->format('d M Y, H:i') . ' WIB');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(24);

        $row = 4;
        $sheet->setCellValue("A{$row}", 'RINGKASAN TOTAL');
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(26);
        $row++;

        $totalSPJ = $rows->count();
        $totalAnggaran = $rows->sum(fn ($s) => (float) ($s->jumlah_anggaran ?? 0));
        $rataRata = $totalSPJ > 0 ? $totalAnggaran / $totalSPJ : 0;

        $totalRows = [
            ['Total Berkas SPJ', $totalSPJ . ' berkas', ''],
            ['Total Anggaran', 'Rp ' . number_format($totalAnggaran, 0, ',', '.'), ''],
            ['Rata-rata per SPJ', 'Rp ' . number_format($rataRata, 0, ',', '.'), ''],
        ];

        foreach ($totalRows as $item) {
            $sheet->setCellValue("A{$row}", $item[0]);
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['size' => 11],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $sheet->setCellValue("C{$row}", $item[1]);
            $sheet->mergeCells("C{$row}:D{$row}");
            $sheet->getStyle("C{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $ACCENT_BG]],
            ]);

            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }

        $row++;

        $sheet->setCellValue("A{$row}", 'BREAKDOWN PER STATUS');
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(26);
        $row++;

        $sheet->setCellValue("A{$row}", 'Status');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", 'Jumlah Berkas');
        $sheet->setCellValue("D{$row}", 'Total Anggaran');
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ]);
        $row++;

        $statusLabels = [
            'draft' => 'Draft',
            'belum_lengkap' => 'Belum Lengkap',
            'proses_verifikasi' => 'Proses Verifikasi',
            'terverifikasi' => 'Terverifikasi',
            'selesai' => 'Selesai',
        ];

        $statusCounts = $rows->groupBy('status')->map->count();
        $statusSum = $rows->groupBy('status')->map(fn ($g) => $g->sum(fn ($s) => (float) ($s->jumlah_anggaran ?? 0)));

        foreach ($statusLabels as $key => $label) {
            $count = $statusCounts->get($key, 0);
            $sum = $statusSum->get($key, 0);

            $sheet->setCellValue("A{$row}", $label);
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("C{$row}", $count);
            $sheet->setCellValue("D{$row}", 'Rp ' . number_format($sum, 0, ',', '.'));

            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                ],
            ]);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $row++;
        }

        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", $totalSPJ);
        $sheet->setCellValue("D{$row}", 'Rp ' . number_format($totalAnggaran, 0, ',', '.'));
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $ACCENT_BG]],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ]);
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'BREAKDOWN PER JENIS SPJ');
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(26);
        $row++;

        $sheet->setCellValue("A{$row}", 'Jenis SPJ');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", 'Jumlah Berkas');
        $sheet->setCellValue("D{$row}", 'Total Anggaran');
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ]);
        $row++;

        $jenisGroups = $rows->groupBy(fn ($s) => $s->jenisSpj->nama ?? 'Tanpa Jenis');

        foreach ($jenisGroups as $jenisNama => $group) {
            $count = $group->count();
            $sum = $group->sum(fn ($s) => (float) ($s->jumlah_anggaran ?? 0));

            $sheet->setCellValue("A{$row}", $jenisNama);
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("C{$row}", $count);
            $sheet->setCellValue("D{$row}", 'Rp ' . number_format($sum, 0, ',', '.'));

            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                ],
            ]);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(24);
    }

    private function buildDataSheet(Spreadsheet $spreadsheet, $rows): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Data SPJ');

        $headers = [
            'No', 'Nomor SPJ', 'Tanggal', 'Tahun', 'Jenis SPJ', 'Kode',
            'Perihal', 'Uraian',
            'Pihak I - Nama', 'Pihak I - NIP/NIK', 'Pihak I - Jabatan', 'Pihak I - Instansi',
            'Pihak II - Nama', 'Pihak II - NIP/NIK', 'Pihak II - Jabatan', 'Pihak II - Instansi',
            'Jumlah Anggaran (Rp)', 'Status', 'Dibuat Oleh', 'Tanggal Dibuat',
            'Diverifikasi Oleh', 'Tanggal Verifikasi', 'Catatan Verifikasi',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $lastCol = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowIdx = 2;
        foreach ($rows as $i => $spj) {
            $sheet->fromArray([
                $i + 1,
                $spj->nomor_spj,
                $spj->tanggal ? $spj->tanggal->format('Y-m-d') : '',
                $spj->tahun_anggaran ?? '',
                $spj->jenisSpj->nama ?? '',
                $spj->jenisSpj->kode ?? '',
                $spj->perihal ?? '',
                $spj->uraian ?? '',
                $spj->pihak1->nama ?? '',
                $spj->pihak1->nip_nik ?? '',
                $spj->pihak1->jabatan ?? '',
                $spj->pihak1->instansi ?? '',
                $spj->pihak2->nama ?? '',
                $spj->pihak2->nip_nik ?? '',
                $spj->pihak2->jabatan ?? '',
                $spj->pihak2->instansi ?? '',
                $spj->jumlah_anggaran !== null ? (float) $spj->jumlah_anggaran : null,
                $spj->status,
                $spj->pembuat->name ?? '',
                $spj->created_at ? $spj->created_at->format('Y-m-d H:i') : '',
                $spj->verifier->name ?? '',
                $spj->verified_at ? $spj->verified_at->format('Y-m-d H:i') : '',
                $spj->catatan_verifikasi ?? '',
            ], null, "A{$rowIdx}");
            $rowIdx++;
        }

        $lastRow = $rowIdx - 1;

        if ($lastRow >= 1) {
            $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('D1D5DB');
        }

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        if ($lastRow >= 1) {
            $sheet->setAutoFilter("A1:{$lastCol}1");
        }

        if ($lastRow >= 2) {
            $sheet->getStyle("Q2:Q{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        if ($lastRow >= 2) {
            for ($r = 2; $r <= $lastRow; $r++) {
                if ($r % 2 === 0) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F8FAFC');
                }
            }
        }
    }

    public function nextNumber(Request $request, SpjNumberService $numberService)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $kodeUnit = $request->user()->kode_unit ?? null;

        return response()->json($numberService->preview($tahun, $kodeUnit));
    }

    private function authorizeView(Request $request, Spj $spj): void
    {
        if (! $request->user()->isAdmin() && $spj->dibuat_oleh !== $request->user()->id) {
            abort(403, 'Anda tidak punya akses ke SPJ ini.');
        }
    }

    public function stats(Request $request)
    {
        $baseQuery = Spj::query();

        if (! $request->user()->isAdmin()) {
            $baseQuery->where('dibuat_oleh', $request->user()->id);
        }

        $summary = [
            'total_spj' => (clone $baseQuery)->count(),
            'total_anggaran' => (float) (clone $baseQuery)->sum('jumlah_anggaran'),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'belum_lengkap' => (clone $baseQuery)->where('status', 'belum_lengkap')->count(),
            'proses_verifikasi' => (clone $baseQuery)->where('status', 'proses_verifikasi')->count(),
            'terverifikasi' => (clone $baseQuery)->where('status', 'terverifikasi')->count(),
            'selesai' => (clone $baseQuery)->where('status', 'selesai')->count(),
        ];

        $byMonth = [];
        $bulanIndo = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $query = (clone $baseQuery)->whereBetween('tanggal', [$start, $end]);

            $byMonth[] = [
                'month' => $date->format('Y-m'),
                'label' => $bulanIndo[(int) $date->format('n')] . ' ' . substr($date->format('Y'), 2),
                'count' => $query->count(),
                'total' => (float) $query->sum('jumlah_anggaran'),
            ];
        }

        $statusLabels = [
            'draft' => 'Draft',
            'belum_lengkap' => 'Belum Lengkap',
            'proses_verifikasi' => 'Proses Verifikasi',
            'terverifikasi' => 'Terverifikasi',
            'selesai' => 'Selesai',
        ];

        $byStatus = [];
        foreach ($statusLabels as $key => $label) {
            $query = (clone $baseQuery)->where('status', $key);
            $byStatus[] = [
                'status' => $key,
                'label' => $label,
                'count' => $query->count(),
                'total' => (float) $query->sum('jumlah_anggaran'),
            ];
        }

        $byJenis = Spj::query()
            ->select('jenis_spj_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(jumlah_anggaran) as total'))
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('dibuat_oleh', $request->user()->id))
            ->groupBy('jenis_spj_id')
            ->with('jenisSpj:id,nama,kode')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->jenis_spj_id,
                'nama' => $row->jenisSpj->nama ?? 'Tanpa Jenis',
                'kode' => $row->jenisSpj->kode ?? '-',
                'count' => (int) $row->count,
                'total' => (float) $row->total,
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();

        return response()->json([
            'summary' => $summary,
            'by_month' => $byMonth,
            'by_status' => $byStatus,
            'by_jenis' => $byJenis,
        ]);
    }

    private function authorizeOwnerOnly(Request $request, Spj $spj): void
    {
        abort_if($request->user()->isAdmin(), 403, 'Admin tidak bisa mengubah/menghapus SPJ (view-only).');
        abort_if($spj->dibuat_oleh !== $request->user()->id, 403, 'Anda bukan pemilik SPJ ini.');
    }
}
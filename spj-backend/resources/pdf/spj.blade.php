cd D:\Aplikasi\SPJ\spj-backend
New-Item -Path resources\views\pdf -ItemType Directory -Force | Out-Null

$template = @'
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SPJ {{ $spj->nomor_spj }}</title>
<style>
@page { margin: 40px 40px 60px 40px; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #0f172a; line-height: 1.5; }

.kop { border-bottom: 3px double #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
.kop-table { width: 100%; border-collapse: collapse; }
.kop-table td { vertical-align: middle; padding: 0; }
.kop-logo { width: 70px; text-align: center; }
.kop-logo-box { width: 60px; height: 60px; background: #0f172a; color: #ffffff; text-align: center; font-weight: bold; font-size: 9pt; line-height: 60px; border-radius: 4px; }
.kop-text { padding-left: 14px; text-align: left; }
.kop-text .gov { font-size: 12pt; font-weight: bold; color: #0f172a; letter-spacing: 0.5px; }
.kop-text .instansi { font-size: 14pt; font-weight: bold; color: #0f172a; margin-top: 2px; }
.kop-text .alamat { font-size: 8pt; color: #64748b; margin-top: 3px; }

.judul-wrap { text-align: center; margin-bottom: 20px; }
.judul { font-size: 13pt; font-weight: bold; color: #0f172a; letter-spacing: 1px; text-decoration: underline; }
.nomor { font-size: 10pt; margin-top: 4px; font-family: DejaVu Sans Mono, monospace; }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px; }
.badge-draft { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
.badge-belum_lengkap { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.badge-proses_verifikasi { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.badge-terverifikasi { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.badge-selesai { background: #0f172a; color: #ffffff; border: 1px solid #0f172a; }

.section { margin-bottom: 18px; }
.section-title { font-size: 10pt; font-weight: bold; color: #ffffff; background: #1e293b; padding: 6px 10px; border-radius: 3px; margin-bottom: 10px; }

table.info { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; }
table.info tr { border-bottom: 1px solid #e2e8f0; }
table.info tr:last-child { border-bottom: none; }
table.info td { padding: 7px 10px; vertical-align: top; }
table.info td.label { width: 30%; background: #f8fafc; font-weight: bold; color: #475569; font-size: 9pt; }
table.info td.value { font-size: 10pt; color: #0f172a; }
.nominal { font-size: 11pt; font-weight: bold; color: #0f172a; }
.terbilang { font-style: italic; color: #64748b; font-size: 8pt; margin-top: 2px; }

table.pihak { width: 100%; border-collapse: separate; border-spacing: 10px 0; }
table.pihak > tr > td { width: 50%; vertical-align: top; padding: 0; }
.pihak-card { border: 1px solid #e2e8f0; border-radius: 4px; padding: 12px; background: #ffffff; }
.pihak-badge { display: inline-block; font-size: 7pt; font-weight: bold; padding: 2px 8px; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.pihak-badge-1 { background: #eff6ff; color: #1d4ed8; }
.pihak-badge-2 { background: #fffbeb; color: #b45309; }
.pihak-nama { font-size: 11pt; font-weight: bold; color: #0f172a; margin-bottom: 2px; }
.pihak-jabatan { font-size: 9pt; color: #475569; margin-bottom: 2px; }
.pihak-meta { font-size: 8pt; color: #64748b; font-family: DejaVu Sans Mono, monospace; margin-top: 4px; }
.ttd-box { margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1; }
.ttd-label { font-size: 7pt; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.ttd-img { max-height: 70px; max-width: 100%; }
.ttd-empty { font-size: 8pt; color: #ba1a1a; font-style: italic; padding: 20px 0; }

table.dokumen { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; }
table.dokumen thead { background: #f1f5f9; }
table.dokumen th { text-align: left; padding: 7px 10px; font-size: 9pt; font-weight: bold; color: #475569; border-bottom: 1px solid #e2e8f0; }
table.dokumen td { padding: 7px 10px; font-size: 9pt; border-bottom: 1px solid #f1f5f9; }
table.dokumen tr:last-child td { border-bottom: none; }
.status-ok { color: #047857; font-weight: bold; }
.status-empty { color: #ba1a1a; font-weight: bold; }

.verifikasi-box { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 4px; padding: 10px 12px; margin-top: 16px; }
.verifikasi-title { font-size: 9pt; font-weight: bold; color: #047857; margin-bottom: 3px; }
.verifikasi-meta { font-size: 8pt; color: #065f46; }
.verifikasi-catatan { font-size: 8pt; color: #065f46; margin-top: 4px; font-style: italic; }
.reject-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; padding: 10px 12px; margin-top: 16px; }
.reject-title { font-size: 9pt; font-weight: bold; color: #b45309; margin-bottom: 3px; }
.reject-catatan { font-size: 8pt; color: #92400e; font-style: italic; }

.pernyataan { margin-top: 20px; padding: 10px 12px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 4px; font-size: 8pt; color: #475569; line-height: 1.6; }
.pernyataan-title { font-weight: bold; font-size: 9pt; color: #0f172a; margin-bottom: 4px; }
.clearfix { clear: both; }
</style>
</head>
<body>

<div class="kop">
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                <div class="kop-logo-box">SPJ</div>
            </td>
            <td class="kop-text">
                <div class="gov">PEMERINTAH DAERAH — SISTEM INFORMASI INTERNAL</div>
                <div class="instansi">SURAT PERTANGGUNGJAWABAN (SPJ)</div>
                <div class="alamat">Dikelola secara terpusat melalui Aplikasi SPJ Internal — Dokumen ini sah tanpa tanda tangan basah</div>
            </td>
        </tr>
    </table>
</div>

<div class="judul-wrap">
    <div class="judul">SURAT PERTANGGUNGJAWABAN</div>
    <div class="nomor">Nomor: {{ $spj->nomor_spj }}</div>
    <div>
        <span class="badge-status badge-{{ $spj->status }}">
            {{ ucwords(str_replace('_', ' ', $spj->status)) }}
        </span>
    </div>
</div>

<div class="section">
    <div class="section-title">A. INFORMASI DASAR</div>
    <table class="info">
        <tr>
            <td class="label">Jenis SPJ</td>
            <td class="value">
                {{ $spj->jenisSpj->nama ?? '-' }}
                @if($spj->jenisSpj && $spj->jenisSpj->kode)
                    <span style="color: #64748b; font-size: 8pt;">({{ $spj->jenisSpj->kode }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Tanggal Pengajuan</td>
            <td class="value">{{ $spj->tanggal ? $spj->tanggal->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tahun Anggaran</td>
            <td class="value">{{ $spj->tahun_anggaran ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Perihal / Nama Kegiatan</td>
            <td class="value">{{ $spj->perihal ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Uraian</td>
            <td class="value">{{ $spj->uraian ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Anggaran</td>
            <td class="value">
                <div class="nominal">Rp {{ number_format((float) ($spj->jumlah_anggaran ?? 0), 0, ',', '.') }},-</div>
                <div class="terbilang">Terbilang: {{ trim(ucwords(\App\Helpers\Terbilang::convert((int) ($spj->jumlah_anggaran ?? 0)))) }} Rupiah</div>
            </td>
        </tr>
        <tr>
            <td class="label">Dibuat Oleh</td>
            <td class="value">
                {{ $spj->pembuat->name ?? '-' }}
                @if($spj->created_at)
                    <span style="color: #64748b; font-size: 8pt;">— {{ $spj->created_at->format('d M Y, H:i') }} WIB</span>
                @endif
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">B. PARA PIHAK PENANDATANGAN</div>
    <table class="pihak">
        <tr>
            <td>
                <div class="pihak-card">
                    <span class="pihak-badge pihak-badge-1">Pihak I</span>
                    <div class="pihak-nama">{{ $spj->pihak1->nama ?? 'Belum dipilih' }}</div>
                    <div class="pihak-jabatan">{{ $spj->pihak1->jabatan ?? '-' }}</div>
                    @if($spj->pihak1)
                        <div class="pihak-jabatan">{{ $spj->pihak1->instansi ?? '-' }}</div>
                        @if($spj->pihak1->nip_nik)
                            <div class="pihak-meta">NIP: {{ $spj->pihak1->nip_nik }}</div>
                        @endif
                    @endif
                    <div class="ttd-box">
                        <div class="ttd-label">Tanda Tangan</div>
                        @if($spj->ttd_pihak1_path && file_exists(storage_path('app/public/' . $spj->ttd_pihak1_path)))
                            <img src="{{ storage_path('app/public/' . $spj->ttd_pihak1_path) }}" class="ttd-img" alt="TTD Pihak I" />
                        @else
                            <div class="ttd-empty">— Belum ada tanda tangan —</div>
                        @endif
                    </div>
                </div>
            </td>
            <td>
                <div class="pihak-card">
                    <span class="pihak-badge pihak-badge-2">Pihak II</span>
                    <div class="pihak-nama">{{ $spj->pihak2->nama ?? 'Belum dipilih' }}</div>
                    <div class="pihak-jabatan">{{ $spj->pihak2->jabatan ?? '-' }}</div>
                    @if($spj->pihak2)
                        <div class="pihak-jabatan">{{ $spj->pihak2->instansi ?? '-' }}</div>
                        @if($spj->pihak2->nip_nik)
                            <div class="pihak-meta">NIP: {{ $spj->pihak2->nip_nik }}</div>
                        @endif
                    @endif
                    <div class="ttd-box">
                        <div class="ttd-label">Tanda Tangan</div>
                        @if($spj->ttd_pihak2_path && file_exists(storage_path('app/public/' . $spj->ttd_pihak2_path)))
                            <img src="{{ storage_path('app/public/' . $spj->ttd_pihak2_path) }}" class="ttd-img" alt="TTD Pihak II" />
                        @else
                            <div class="ttd-empty">— Belum ada tanda tangan —</div>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">C. DOKUMEN TERLAMPIR</div>
    @if($spj->dokumen && $spj->dokumen->count() > 0)
        <table class="dokumen">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 45%;">Nama Dokumen</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 35%;">File</th>
                </tr>
            </thead>
            <tbody>
                @foreach($spj->dokumen as $idx => $dok)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $dok->dokumenWajib->nama_dokumen ?? '-' }}</strong>
                            @if($dok->dokumenWajib && !$dok->dokumenWajib->wajib)
                                <span style="color: #94a3b8; font-size: 7pt;">(opsional)</span>
                            @endif
                        </td>
                        <td>
                            @if($dok->status === 'sudah')
                                <span class="status-ok">✓ Terlampir</span>
                            @else
                                <span class="status-empty">✗ Belum</span>
                            @endif
                        </td>
                        <td style="font-size: 8pt; color: #64748b;">{{ $dok->file_nama_asli ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="padding: 12px; text-align: center; color: #94a3b8; font-style: italic; border: 1px dashed #cbd5e1; border-radius: 4px;">
            Belum ada dokumen terlampir
        </div>
    @endif
</div>

@if($spj->status === 'terverifikasi' && $spj->verified_at)
    <div class="verifikasi-box">
        <div class="verifikasi-title">✓ TERVERIFIKASI</div>
        <div class="verifikasi-meta">
            Diverifikasi oleh: <strong>{{ $spj->verifier->name ?? 'Admin' }}</strong>
            pada {{ $spj->verified_at->translatedFormat('d F Y, H:i') }} WIB
        </div>
        @if($spj->catatan_verifikasi)
            <div class="verifikasi-catatan">Catatan: "{{ $spj->catatan_verifikasi }}"</div>
        @endif
    </div>
@elseif($spj->status === 'belum_lengkap' && $spj->catatan_verifikasi)
    <div class="reject-box">
        <div class="reject-title">⚠ MEMERLUKAN REVISI</div>
        <div class="reject-catatan">Catatan: "{{ $spj->catatan_verifikasi }}"</div>
    </div>
@endif

<div class="pernyataan">
    <div class="pernyataan-title">PERNYATAAN INTEGRITAS & TANGGUNG JAWAB MUTLAK (SPTJ.M)</div>
    Saya menyatakan bahwa seluruh data, berkas lampiran, dan pengesahan tanda tangan di atas
    telah sesuai dengan ketentuan pertanggungjawaban keuangan yang sah. Segala bentuk perbedaan
    atau kekeliruan administrasi menjadi tanggung jawab pengusul secara hukum kedinasan.
</div>

</body>
</html>
'@

$template | Out-File -FilePath resources\views\pdf\spj.blade.php -Encoding UTF8

Test-Path resources\views\pdf\spj.blade.php
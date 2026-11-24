@php
    // ---------- Persiapan data tampilan ----------
    $labelStatus = [
        'draft' => 'Draft',
        'belum_lengkap' => 'Belum Lengkap',
        'proses_verifikasi' => 'Proses Verifikasi',
        'terverifikasi' => 'Terverifikasi',
        'selesai' => 'Selesai',
    ][$spj->status] ?? ucwords(str_replace('_', ' ', (string) $spj->status));

    // Aplikasi memakai locale 'en' dan timezone UTC, jadi format tanggal dipaksa
    // ke bahasa Indonesia dan WIB di sini supaya tidak muncul "November" versi Inggris
    // atau jam yang selisih 7 jam.
    $tgl = fn ($d, $fmt = 'd F Y') => $d ? $d->copy()->locale('id')->translatedFormat($fmt) : '-';
    $tglWib = fn ($d, $fmt = 'd F Y, H:i') => $d
        ? $d->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat($fmt) . ' WIB'
        : '-';

    // Tanda tangan dibaca dari disk lalu disematkan langsung (data URI),
    // jadi tidak bergantung pada format path Windows/Linux.
    $ttdSrc = function (?string $path) {
        if (! $path) {
            return null;
        }
        $full = storage_path('app/public/' . $path);
        if (! is_file($full)) {
            return null;
        }
        $mime = str_ends_with(strtolower($full), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($full));
    };
    $ttd1 = $ttdSrc($spj->ttd_pihak1_path);
    $ttd2 = $ttdSrc($spj->ttd_pihak2_path);

    $instansi = $spj->pihak1->instansi ?? config('app.name', 'SPJ');
    $dokTotal = $spj->dokumen ? $spj->dokumen->count() : 0;
    $dokSudah = $spj->dokumen ? $spj->dokumen->where('status', 'sudah')->count() : 0;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>SPJ {{ $spj->nomor_spj }}</title>
    <style>
        @page { margin: 50px 50px 70px 50px; }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            line-height: 1.45;
        }

        /* ---------- Kop ---------- */
        table.kop { width: 100%; border-collapse: collapse; }
        table.kop td { vertical-align: middle; padding: 0; }
        td.kop-logo { width: 54px; }
        .logo-box {
            width: 46px; height: 46px;
            background-color: #0f172a; color: #ffffff;
            text-align: center; font-size: 13px; font-weight: bold;
            line-height: 46px; border-radius: 6px;
        }
        .kop-instansi { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .kop-sub { font-size: 8.5px; color: #64748b; margin-top: 2px; }
        .kop-line { border-bottom: 2px solid #0f172a; margin: 10px 0 16px 0; }

        /* ---------- Judul ---------- */
        .judul-wrap { text-align: center; margin-bottom: 16px; }
        .judul { font-size: 14px; font-weight: bold; letter-spacing: 1px; }
        .nomor { font-size: 10px; margin-top: 3px; color: #334155; }
        .badge {
            display: inline-block; margin-top: 7px; padding: 3px 12px;
            font-size: 8px; font-weight: bold; letter-spacing: 0.5px;
            text-transform: uppercase; border-radius: 10px;
            background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;
        }
        .badge-belum_lengkap { background-color: #fffbeb; color: #b45309; border-color: #fcd34d; }
        .badge-proses_verifikasi { background-color: #eff6ff; color: #1d4ed8; border-color: #93c5fd; }
        .badge-terverifikasi { background-color: #ecfdf5; color: #047857; border-color: #6ee7b7; }
        .badge-selesai { background-color: #0f172a; color: #ffffff; border-color: #0f172a; }

        .keep { page-break-inside: avoid; }

        /* ---------- Seksi ---------- */
        .section-title {
            font-size: 9.5px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.4px; color: #ffffff; background-color: #1e293b;
            padding: 5px 10px; margin: 16px 0 8px 0;
            page-break-after: avoid;
        }

        table.info { width: 100%; border-collapse: collapse; }
        table.info td { padding: 5px 8px; vertical-align: top; border-bottom: 1px solid #e2e8f0; }
        table.info td.label { width: 28%; color: #475569; background-color: #f8fafc; }
        .nominal { font-size: 12px; font-weight: bold; }
        .terbilang { font-size: 8.5px; font-style: italic; color: #475569; margin-top: 1px; }
        .muted { color: #64748b; font-size: 8.5px; }

        /* ---------- Pihak & tanda tangan ---------- */
        table.pihak { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
        table.pihak td {
            width: 50%; vertical-align: top; padding: 10px;
            border: 1px solid #cbd5e1; border-radius: 4px; text-align: center;
        }
        .pihak-peran { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
        .ttd-area { height: 70px; margin: 6px 0; }
        .ttd-img { max-height: 66px; max-width: 150px; }
        .ttd-kosong { padding-top: 26px; font-size: 8.5px; font-style: italic; color: #94a3b8; }
        .pihak-nama { font-size: 10.5px; font-weight: bold; border-top: 1px solid #0f172a; padding-top: 4px; }

        /* ---------- Dokumen ---------- */
        table.dokumen { width: 100%; border-collapse: collapse; }
        table.dokumen th, table.dokumen td {
            border: 1px solid #cbd5e1; padding: 5px 7px; font-size: 9px; text-align: left; vertical-align: top;
        }
        table.dokumen th { background-color: #f1f5f9; font-size: 8.5px; text-transform: uppercase; }
        .ok { color: #047857; font-weight: bold; }
        .belum { color: #b91c1c; font-weight: bold; }

        /* ---------- Kotak verifikasi ---------- */
        .box { margin-top: 14px; padding: 9px 12px; border-radius: 4px; page-break-inside: avoid; }
        .box-ok { background-color: #ecfdf5; border: 1px solid #6ee7b7; }
        .box-revisi { background-color: #fffbeb; border: 1px solid #fcd34d; }
        .box-title { font-size: 9.5px; font-weight: bold; margin-bottom: 3px; }

        /* ---------- Footer tiap halaman ---------- */
        .footer {
            position: fixed; left: 0; right: 0; bottom: -42px;
            border-top: 1px solid #cbd5e1; padding-top: 5px;
            font-size: 8px; color: #64748b;
        }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer td { padding: 0; font-size: 8px; color: #64748b; }
        .footer td.kanan { text-align: right; }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>

    <div class="footer">
        <table>
            <tr>
                <td>SPJ {{ $spj->nomor_spj }} &bull; Dicetak {{ $tglWib(now()) }}</td>
                <td class="kanan">Halaman <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    {{-- ===== Kop ===== --}}
    <table class="kop">
        <tr>
            <td class="kop-logo"><div class="logo-box">SPJ</div></td>
            <td>
                <div class="kop-instansi">{{ $instansi }}</div>
                <div class="kop-sub">Aplikasi Surat Pertanggungjawaban &bull; Dokumen dihasilkan otomatis oleh sistem</div>
            </td>
        </tr>
    </table>
    <div class="kop-line"></div>

    {{-- ===== Judul ===== --}}
    <div class="judul-wrap">
        <div class="judul">SURAT PERTANGGUNGJAWABAN</div>
        <div class="nomor">Nomor: <strong>{{ $spj->nomor_spj }}</strong></div>
        <span class="badge badge-{{ $spj->status }}">{{ $labelStatus }}</span>
    </div>

    {{-- ===== A. Informasi dasar ===== --}}
    <div class="section-title">A. Informasi Dasar</div>
    <table class="info">
        <tr>
            <td class="label">Jenis SPJ</td>
            <td>
                {{ $spj->jenisSpj->nama ?? '-' }}
                @if($spj->jenisSpj && $spj->jenisSpj->kode)
                    <span class="muted">({{ $spj->jenisSpj->kode }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td>{{ $tgl($spj->tanggal) }}</td>
        </tr>
        <tr>
            <td class="label">Tahun Anggaran</td>
            <td>{{ $spj->tahun_anggaran ?? '-' }}</td>
        </tr>
        @if($spj->jenisSpj && $spj->jenisSpj->mak)
            <tr>
                <td class="label">MAK</td>
                <td>{{ $spj->jenisSpj->mak }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Perihal / Kegiatan</td>
            <td>{{ $spj->perihal ?? '-' }}</td>
        </tr>
        @if($spj->uraian)
            <tr>
                <td class="label">Uraian</td>
                <td>{{ $spj->uraian }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Jumlah Anggaran</td>
            <td>
                @if(! is_null($spj->jumlah_anggaran))
                    <div class="nominal">Rp {{ number_format((float) $spj->jumlah_anggaran, 0, ',', '.') }}</div>
                    <div class="terbilang">Terbilang: {{ ucfirst(trim(\App\Helpers\Terbilang::convert((int) $spj->jumlah_anggaran))) }} rupiah</div>
                @else
                    -
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Dibuat oleh</td>
            <td>
                {{ $spj->pembuat->name ?? '-' }}
                <span class="muted">&bull; {{ $tglWib($spj->created_at) }}</span>
            </td>
        </tr>
    </table>

    {{-- ===== B. Pihak & tanda tangan ===== --}}
    <div class="keep">
    <div class="section-title">B. Pihak Penandatangan</div>
    <table class="pihak">
        <tr>
            <td>
                <div class="pihak-peran">Pihak I</div>
                <div class="ttd-area">
                    @if($ttd1)
                        <img class="ttd-img" src="{{ $ttd1 }}" alt="Tanda tangan Pihak I">
                    @else
                        <div class="ttd-kosong">Belum ada tanda tangan</div>
                    @endif
                </div>
                <div class="pihak-nama">{{ $spj->pihak1->nama ?? 'Belum dipilih' }}</div>
                <div>{{ $spj->pihak1->jabatan ?? '-' }}</div>
                @if($spj->pihak1 && $spj->pihak1->instansi)
                    <div class="muted">{{ $spj->pihak1->instansi }}</div>
                @endif
                @if($spj->pihak1 && $spj->pihak1->nip_nik)
                    <div class="muted">NIP/NIK: {{ $spj->pihak1->nip_nik }}</div>
                @endif
                @if($ttd1 && $spj->ttd_pihak1_at)
                    <div class="muted">Ditandatangani {{ $tglWib($spj->ttd_pihak1_at) }}</div>
                @endif
            </td>
            <td>
                <div class="pihak-peran">Pihak II</div>
                <div class="ttd-area">
                    @if($ttd2)
                        <img class="ttd-img" src="{{ $ttd2 }}" alt="Tanda tangan Pihak II">
                    @else
                        <div class="ttd-kosong">Belum ada tanda tangan</div>
                    @endif
                </div>
                <div class="pihak-nama">{{ $spj->pihak2->nama ?? 'Belum dipilih' }}</div>
                <div>{{ $spj->pihak2->jabatan ?? '-' }}</div>
                @if($spj->pihak2 && $spj->pihak2->instansi)
                    <div class="muted">{{ $spj->pihak2->instansi }}</div>
                @endif
                @if($spj->pihak2 && $spj->pihak2->nip_nik)
                    <div class="muted">NIP/NIK: {{ $spj->pihak2->nip_nik }}</div>
                @endif
                @if($ttd2 && $spj->ttd_pihak2_at)
                    <div class="muted">Ditandatangani {{ $tglWib($spj->ttd_pihak2_at) }}</div>
                @endif
            </td>
        </tr>
    </table>
    </div>

    {{-- ===== C. Kelengkapan dokumen ===== --}}
    <div class="section-title">C. Kelengkapan Dokumen ({{ $dokSudah }} dari {{ $dokTotal }} terlampir)</div>
    <table class="dokumen">
        <thead>
            <tr>
                <th style="width: 26px;">No</th>
                <th>Nama Dokumen</th>
                <th style="width: 52px;">Sifat</th>
                <th style="width: 70px;">Status</th>
                <th style="width: 32%;">Nama File</th>
            </tr>
        </thead>
        <tbody>
            @forelse($spj->dokumen as $i => $dok)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $dok->dokumenWajib->nama_dokumen ?? '-' }}</td>
                    <td>{{ ($dok->dokumenWajib->wajib ?? false) ? 'Wajib' : 'Opsional' }}</td>
                    <td class="{{ $dok->status === 'sudah' ? 'ok' : 'belum' }}">
                        {{ $dok->status === 'sudah' ? 'Terlampir' : 'Belum' }}
                    </td>
                    <td class="muted">{{ $dok->status === 'sudah' ? ($dok->file_nama_asli ?? '-') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Tidak ada dokumen wajib untuk jenis SPJ ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===== Hasil verifikasi ===== --}}
    @if(in_array($spj->status, ['terverifikasi', 'selesai'], true))
        <div class="box box-ok">
            <div class="box-title">SPJ telah diverifikasi</div>
            Diverifikasi oleh <strong>{{ $spj->verifier->name ?? 'Admin' }}</strong>
            pada {{ $tglWib($spj->verified_at) }}
            @if($spj->catatan_verifikasi)
                <br>Catatan: {{ $spj->catatan_verifikasi }}
            @endif
        </div>
    @elseif($spj->status === 'belum_lengkap' && $spj->catatan_verifikasi)
        <div class="box box-revisi">
            <div class="box-title">Perlu perbaikan</div>
            Catatan admin: {{ $spj->catatan_verifikasi }}
        </div>
    @endif

</body>
</html>

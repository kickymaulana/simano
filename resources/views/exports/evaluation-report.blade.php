<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><title>Rekap Evaluasi</title><style>body{font-family:DejaVu Sans, sans-serif;font-size:12px;color:#172033}h1{font-size:20px}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{border:1px solid #cbd5e1;padding:7px;text-align:left}th{background:#e2e8f0}.meta{color:#475569}</style></head>
<body>
    <h1>Rekap Evaluasi Kinerja</h1>
    <p class="meta">Periode: {{ $period ? sprintf('%02d/%d', $period->month, $period->year) : 'Semua periode' }}</p>
    <table><thead><tr><th>Periode</th><th>Target</th><th>Kategori</th><th>Jumlah Evaluasi</th><th>Rata-rata</th></tr></thead><tbody>
        @forelse ($rows as $row)
            <tr><td>{{ sprintf('%02d/%d', $row->period->month, $row->period->year) }}</td><td>{{ $row->target->name }}</td><td>{{ $row->target_category }}</td><td>{{ $row->evaluation_count }}</td><td>{{ number_format((float) $row->average_score, 2) }}</td></tr>
        @empty
            <tr><td colspan="5">Belum ada data evaluasi.</td></tr>
        @endforelse
    </tbody></table>
</body>
</html>

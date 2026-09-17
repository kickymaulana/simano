<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Evaluasi per Atasan</title>
    <style>
        @page { margin: 28px; }
        body { color: #1e293b; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { color: #1d4ed8; font-size: 18px; margin: 0 0 5px; }
        .meta { border-left: 4px solid #2563eb; margin: 0 0 16px; padding: 7px 10px; }
        .meta p { margin: 2px 0; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; vertical-align: top; }
        th { background: #1d4ed8; color: #fff; text-align: center; }
        .number { text-align: center; width: 5%; }
        .question { width: 59%; }
        .score { text-align: center; width: 7%; }
        .s5 { background: #dcfce7; }.s4 { background: #ecfccb; }.s3 { background: #fef9c3; }.s2 { background: #ffedd5; }.s1 { background: #fee2e2; }
        .average td { background: #dbeafe; color: #1e3a8a; font-weight: bold; }
        .notes { background: #fefce8; border: 1px solid #fde68a; margin-top: 14px; padding: 10px; }
        .notes p { margin: 3px 0; }
    </style>
</head>
<body>
    <h1>Laporan Evaluasi per Atasan</h1>
    <div class="meta"><p><strong>{{ $target->name }}</strong>{{ $target->position ? ' - '.$target->position->name : '' }}</p><p>Periode: {{ sprintf('%02d/%d', $period->month, $period->year) }} · NIK: {{ $target->nik ?: '-' }} · Jumlah Penilai: {{ $evaluatorCount }} orang</p></div>
    <table><thead><tr><th rowspan="2" class="number">No.</th><th rowspan="2" class="question">Pertanyaan</th><th colspan="5">Persentase Jawaban</th></tr><tr><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr></thead><tbody>
        @foreach ($rows as $row)
            <tr><td class="number">{{ $row['number'] }}</td><td class="question">{{ $row['text'] }}</td><td class="score s5">{{ $row['scores'][5] }}%</td><td class="score s4">{{ $row['scores'][4] }}%</td><td class="score s3">{{ $row['scores'][3] }}%</td><td class="score s2">{{ $row['scores'][2] }}%</td><td class="score s1">{{ $row['scores'][1] }}%</td></tr>
        @endforeach
        <tr class="average"><td colspan="2">Average</td>@foreach ([5, 4, 3, 2, 1] as $score)<td class="score">{{ $rows->count() ? round($rows->avg(fn (array $row) => $row['scores'][$score])) : 0 }}%</td>@endforeach</tr>
    </tbody></table>
    <div class="notes"><p>*). Berikut ini penilaian BAWAHAN ANDA terhadap Anda sebagai Atasan</p><p>*). Mohon untuk diperbaiki untuk setiap item yang masih kurang</p><p>*). Penilaian ini akan kita laksanakan kembali 2 bulan lagi</p><p>*). Target Untuk Nilai 4 &amp; 5 Minimal 80%</p></div>
</body>
</html>

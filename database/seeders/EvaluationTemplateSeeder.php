<?php

namespace Database\Seeders;

use App\Models\EvaluationTemplate;
use Illuminate\Database\Seeder;

class EvaluationTemplateSeeder extends Seeder
{
    /**
     * Seed the evaluation templates with their questions.
     */
    public function run(): void
    {
        $this->seedTemplate('atasan', [
            'Atasan memperlakukan anda sebagai bawahan dengan baik dan benar',
            'Atasan menegur anda jika ada kesalahan dengan sopan & membantu anda cara untuk perbaiki kesalahan dgn benar',
            'Atasan ada memberi training dengan cukup setiap jenis pekerjaan di bagian anda',
            'Atasan memberikan penjelasan details jenis reject, target kerja & cara bagaimana untuk anda capai target',
            'Atasan membantu anda jika ada keluhan anda terkait pekerjaan anda dan memberikan solusi kepada anda',
            'Atasan memberikan Bimbingan & Arahan, motivasi dan semangat kerja kepada anda setiap hari',
            'Atasan ada mengajarkan anda terkait peraturan kerja dan juga peraturan perusahaan setiap hari',
            'Atasan menyediakan fasilitas, alat kerja & perlengkapan kerja anda dalam bekerja sesuai yang dibutuhkan',
            'Atasan membantu anda jika ada alat & perlengkapan kerja yang tidak tersedia saat anda butuh',
            'Atasan membantu anda untuk f.up jika ada alat & perlengkapan kerja yang rusak dan mencari alternatif lain sementara',
            'Atasan memperhatikan lingkungan kerja yang nyaman bagi anda saat bekerja',
            'Atasan menerima masukan, ide anda untuk mempermudah pekerjaan atau demi kebaikan kerja termasuk quality',
            'Atasan melengkapi Alat Pelindung Diri anda dalam bekerja & menegur anda jika tidak menggunakan APD',
            'Atasan membantu jika ada keluhan terkait APD karyawan kurang atau tidak nyaman',
        ]);

        $this->seedTemplate('hrd', [
            'hrd memperlakukan anda sebagai bawahan dengan baik dan benar',
            'hrd menegur jika ada kesalahan dengan sopan & membantu anda cara untuk perbaiki kesalahan dgn benar',
            'hrd ada memberi training dengan cukup setiap jenis peraturan perusahaan dan pekerjaan di bagian anda',
            'hrd memberikan penjelasan details terkait pengurusan administrasi yagn berhubungan dengan HRD',
            'hrd membantu anda jika ada keluhan anda terkait pekerjaan anda dan memberikan solusi kepada anda',
            'hrd memberikan Bimbingan & Arahan, motivasi dan semangat kerja kepada anda setiap hari',
            'hrd ada mengajarkan anda terkait peraturan kerja dan juga peraturan perusahaan setiap hari',
            'hrd menyediakan fasilitas perusahaan untuk kebutuhan karyawan (toilet, kantin, dll)',
            'hrd membantu anda jika ada kerusakan dan ketidaksesuaian pada fasilitas saat digunakan karyawan',
            'hrd membantu anda untuk f.up jika ada fasiltias perusahaan yang rusak dan mencari alternatif lain sementara',
            'hrd memperhatikan lingkungan kerja yang nyaman bagi anda saat bekerja',
            'hrd menerima masukan, ide anda untuk mempermudah pekerjaan atau demi kebaikan kerja (keluhan karyawan)',
            'hrd melengkapi Alat Pelindung Diri anda dalam bekerja & menegur anda jika tidak menggunakan APD',
            'hrd membantu jika ada keluhan terkait APD karyawan kurang atau tidak nyaman',
        ]);
    }

    /**
     * @param  array<int, string>  $questions
     */
    private function seedTemplate(string $category, array $questions): void
    {
        $template = EvaluationTemplate::updateOrCreate(
            ['target_category' => $category],
            ['active' => true]
        );

        foreach ($questions as $index => $text) {
            $template->questions()->updateOrCreate(
                ['question_number' => $index + 1],
                ['question_text' => $text, 'active' => true]
            );
        }
    }
}

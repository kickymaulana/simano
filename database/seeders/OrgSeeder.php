<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Factory;
use App\Models\Position;
use Illuminate\Database\Seeder;

class OrgSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            'DIREKSI' => 0,
            'GM/FM' => 1,
            'SEKRETARIS' => 2,
            'FM' => 2,
            'MANAGER' => 3,
            'SUPERVISOR' => 4,
            'LEADER' => 5,
            'OPERATOR' => 6,
        ];

        foreach ($positions as $name => $level) {
            Position::updateOrCreate(['name' => $name], ['level' => $level]);
        }

        foreach (['KIM', 'DALU 1', 'DALU 2'] as $name) {
            Factory::updateOrCreate(['name' => $name]);
        }

        $departments = [
            'MOULD', 'FILLING', 'WASHING', 'CUCI CELUP', 'SPRAY ON HALUS', 'OVEN',
            'ASAH / GRATING', 'QC', 'TEXTURE / SPK', 'MOULD DESIGN', 'QA', 'FQC',
            'IT', 'MTC', 'GUDANG', 'COMPOUND', 'CASTING', 'HRD', 'SOLAR', 'QS',
            'FINISH GOOD', 'SANITARY', 'CRUSHER', 'PPIC', 'RND', 'ACCOUNTING',
            'GLAZE', 'PURCHASING', 'WATER TREATMENT PLANT', 'PRODUKSI', 'UMUM',
            'EXIM', 'TAPAK', 'PROJECT', 'PACKING', 'CNC', 'HSE', 'QC Supporting',
        ];

        foreach ($departments as $name) {
            Department::updateOrCreate(['name' => $name]);
        }
    }
}

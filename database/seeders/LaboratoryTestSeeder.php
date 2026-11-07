<?php

namespace Database\Seeders;

use App\Models\LaboratoryTest;
use Illuminate\Database\Seeder;

/** Reference catalogue of common test names (editable from Laboratory › Tests). */
class LaboratoryTestSeeder extends Seeder
{
    public function run(): void
    {
        $tests = [
            'Haematology' => ['Complete Blood Count (CBC)' => 'Blood', 'Erythrocyte Sedimentation Rate (ESR)' => 'Blood', 'Prothrombin Time / INR' => 'Blood', 'Blood Group & Rh' => 'Blood'],
            'Biochemistry' => ['Fasting Blood Glucose' => 'Blood', 'Random Blood Glucose' => 'Blood', 'HbA1c' => 'Blood', 'Lipid Profile' => 'Blood', 'Liver Function Tests (LFT)' => 'Blood', 'Renal Function Tests (RFT)' => 'Blood', 'Serum Electrolytes' => 'Blood', 'Serum Uric Acid' => 'Blood', 'Vitamin D (25-OH)' => 'Blood', 'Vitamin B12' => 'Blood', 'Serum Ferritin' => 'Blood', 'C-Reactive Protein (CRP)' => 'Blood'],
            'Endocrinology' => ['Thyroid Profile (TSH, T3, T4)' => 'Blood', 'TSH' => 'Blood'],
            'Microbiology' => ['Urine Culture & Sensitivity' => 'Urine', 'Blood Culture' => 'Blood', 'Stool Culture' => 'Stool'],
            'Clinical Pathology' => ['Urine Routine Examination' => 'Urine', 'Stool Routine Examination' => 'Stool'],
            'Serology' => ['Hepatitis B Surface Antigen (HBsAg)' => 'Blood', 'Anti-HCV' => 'Blood', 'Typhidot' => 'Blood', 'Dengue NS1' => 'Blood', 'Malaria Parasite (MP)' => 'Blood'],
            'Radiology' => ['Chest X-Ray' => null, 'Ultrasound Abdomen' => null, 'ECG' => null],
        ];

        foreach ($tests as $category => $items) {
            foreach ($items as $name => $sample) {
                LaboratoryTest::query()->firstOrCreate(['name' => $name, 'category' => $category], ['sample_type' => $sample, 'is_active' => true]);
            }
        }
    }
}

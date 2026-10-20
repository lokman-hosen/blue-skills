<?php

namespace Database\Seeders;

use App\Domain\Config\Enums\LanguageStatus;
use App\Domain\Config\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['en', 'English', 'English', false, LanguageStatus::Active],
            ['bn', 'বাংলা', 'Bangla', false, LanguageStatus::Active],
            ['hi', 'हिन्दी', 'Hindi', false, LanguageStatus::Active],
            ['ur', 'اردو', 'Urdu', true, LanguageStatus::Active],
            ['ne', 'नेपाली', 'Nepali', false, LanguageStatus::Active],
            ['si', 'සිංහල', 'Sinhala', false, LanguageStatus::InReview],
            ['ta', 'தமிழ்', 'Tamil', false, LanguageStatus::InReview],
            ['ar', 'العربية', 'Arabic', true, LanguageStatus::Planned],
        ];

        foreach ($languages as [$code, $native, $english, $rtl, $status]) {
            Language::query()->updateOrCreate(['code' => $code], [
                'native_name' => $native,
                'english_name' => $english,
                'rtl' => $rtl,
                'status' => $status,
            ]);
        }
    }
}

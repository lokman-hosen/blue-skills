<?php

namespace App\Domain\Worker\Voice;

use App\Domain\Worker\Contracts\SpeechToText;
use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Exceptions\TranscriptionFailed;

/**
 * Local development stand-in: ignores the recording and returns the prototype's sample
 * answer for the question, so the whole Voice-to-CV flow can be tried without a provider.
 * Refused in production.
 */
class SampleSpeechToText implements SpeechToText
{
    /** @var array<string, array<string, string>> */
    private const ANSWERS = [
        'en' => [
            'occupation' => 'I am an electrician. I mostly do industrial wiring.',
            'experience' => 'Eight years.',
            'countries' => 'Five years in Bangladesh and three years in Dubai.',
            'skills' => 'Industrial wiring, DB installation and maintenance.',
            'passport' => 'Yes, I have a passport. It is valid until 2030.',
            'availability' => 'I can start immediately.',
            'destinations' => 'Dubai, or Poland.',
        ],
        'bn' => [
            'occupation' => 'আমি ইলেকট্রিশিয়ান, বেশিরভাগ ইন্ডাস্ট্রিয়াল ওয়্যারিং করি।',
            'experience' => 'আট বছর।',
            'countries' => 'বাংলাদেশে পাঁচ বছর, দুবাইতে তিন বছর।',
            'skills' => 'ইন্ডাস্ট্রিয়াল ওয়্যারিং, ডিবি ইনস্টলেশন আর মেইনটেন্যান্স।',
            'passport' => 'হ্যাঁ, পাসপোর্ট আছে, মেয়াদ ২০৩০ পর্যন্ত।',
            'availability' => 'এখনই শুরু করতে পারব।',
            'destinations' => 'দুবাই, না হলে পোল্যান্ড।',
        ],
    ];

    public function transcribe(string $audioPath, string $locale, VoiceQuestion $question): string
    {
        if (app()->isProduction()) {
            throw new TranscriptionFailed(TranscriptionFailed::UNAVAILABLE, 'The sample transcriber must not be used in production.');
        }

        return (self::ANSWERS[$locale] ?? self::ANSWERS['en'])[$question->value];
    }

    public function available(): bool
    {
        return ! app()->isProduction();
    }
}

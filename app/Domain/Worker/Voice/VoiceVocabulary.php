<?php

namespace App\Domain\Worker\Voice;

/**
 * Hand-written words the rules-based extractor recognises besides the English taxonomy and
 * country names: Bangla terms from the prototype, common city names and number words.
 * Extend it with reviewed vocabulary only (CLAUDE.md: never machine-translate silently).
 */
final class VoiceVocabulary
{
    /** @var array<string, list<string>> country code => aliases */
    public const COUNTRIES = [
        'AE' => ['uae', 'emirates', 'dubai', 'abu dhabi', 'sharjah', 'দুবাই', 'আমিরাত', 'আবুধাবি', 'শারজাহ'],
        'SA' => ['saudi', 'ksa', 'riyadh', 'jeddah', 'dammam', 'সৌদি', 'রিয়াদ', 'জেদ্দা'],
        'QA' => ['qatar', 'doha', 'কাতার', 'দোহা'],
        'OM' => ['oman', 'muscat', 'ওমান', 'মাস্কাট'],
        'KW' => ['kuwait', 'কুয়েত'],
        'BH' => ['bahrain', 'বাহরাইন'],
        'MY' => ['malaysia', 'kuala lumpur', 'মালয়েশিয়া'],
        'SG' => ['singapore', 'সিঙ্গাপুর'],
        'PL' => ['poland', 'warsaw', 'পোল্যান্ড'],
        'CZ' => ['czech', 'czechia', 'prague', 'চেক'],
        'RO' => ['romania', 'রোমানিয়া'],
        'JP' => ['japan', 'tokyo', 'জাপান'],
        'BD' => ['bangladesh', 'dhaka', 'বাংলাদেশ', 'ঢাকা'],
        'IN' => ['india', 'ভারত', 'ইন্ডিয়া'],
        'NP' => ['nepal', 'নেপাল'],
        'PK' => ['pakistan', 'পাকিস্তান'],
        'LK' => ['sri lanka', 'শ্রীলঙ্কা'],
    ];

    /** @var array<string, list<string>> occupation slug => aliases */
    public const OCCUPATIONS = [
        'electrician' => ['ইলেকট্রিশিয়ান', 'ইলেক্ট্রিশিয়ান', 'বিদ্যুৎ মিস্ত্রি'],
        'electrical-technician' => ['ইলেকট্রিক্যাল টেকনিশিয়ান'],
        'hvac-technician' => ['hvac', 'air conditioning', 'এসি টেকনিশিয়ান'],
        'plumber' => ['প্লাম্বার'],
        'mason' => ['রাজমিস্ত্রি'],
        'carpenter' => ['কাঠমিস্ত্রি'],
        'painter' => ['রংমিস্ত্রি'],
        'steel-fixer' => ['রড মিস্ত্রি'],
        'warehouse-operative' => ['warehouse', 'গুদাম'],
        'welder' => ['welding', 'ওয়েল্ডার', 'ঝালাই'],
        'driver' => ['ড্রাইভার', 'চালক'],
    ];

    /** @var array<string, list<string>> specialization slug => aliases */
    public const SPECIALIZATIONS = [
        'industrial-electrician' => ['industrial', 'ইন্ডাস্ট্রিয়াল'],
        'building-electrician' => ['building', 'বিল্ডিং'],
    ];

    /** @var array<string, list<string>> skill slug => aliases */
    public const SKILLS = [
        'industrial-wiring' => ['ইন্ডাস্ট্রিয়াল ওয়্যারিং'],
        'db-installation' => ['db installation', 'distribution board', 'ডিবি ইনস্টলেশন'],
        'maintenance' => ['মেইনটেন্যান্স', 'রক্ষণাবেক্ষণ'],
        'cable-termination' => ['ক্যাবল টার্মিনেশন'],
        'troubleshooting' => ['ট্রাবলশুটিং'],
        'conduit-bending' => ['কন্ডুইট'],
    ];

    /** @var array<string, int> */
    public const NUMBER_WORDS = [
        'zero' => 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6,
        'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10, 'eleven' => 11, 'twelve' => 12,
        'thirteen' => 13, 'fourteen' => 14, 'fifteen' => 15, 'sixteen' => 16, 'seventeen' => 17,
        'eighteen' => 18, 'nineteen' => 19, 'twenty' => 20, 'thirty' => 30,
        'এক' => 1, 'দুই' => 2, 'তিন' => 3, 'চার' => 4, 'পাঁচ' => 5, 'ছয়' => 6, 'সাত' => 7,
        'আট' => 8, 'নয়' => 9, 'দশ' => 10, 'এগারো' => 11, 'বারো' => 12, 'তেরো' => 13,
        'চৌদ্দ' => 14, 'পনেরো' => 15, 'ষোল' => 16, 'সতেরো' => 17, 'আঠারো' => 18, 'উনিশ' => 19,
        'বিশ' => 20,
    ];

    /** @var list<string> */
    public const YES = ['yes', 'have', 'got', 'valid', 'হ্যাঁ', 'আছে'];

    /** @var list<string> */
    public const NO = ['no', 'not', "don't", 'dont', 'none', 'নেই', 'নাই', 'না'];

    /** @var list<string> */
    public const APPLIED = ['applied', 'apply', 'processing', 'আবেদন'];

    /** @var list<string> */
    public const EXPIRED = ['expired', 'মেয়াদ শেষ', 'মেয়াদোত্তীর্ণ'];

    /** @var list<string> */
    public const IMMEDIATELY = ['immediately', 'right away', 'now', 'today', 'এখনই', 'এখন', 'আজই'];

    /** @var list<string> */
    public const NOT_AVAILABLE = ['not available', "can't", 'cannot', 'পারব না'];

    /** @var list<string> */
    public const MONTH = ['month', 'মাস'];

    /** @var list<string> */
    public const DAY = ['day', 'দিন'];
}

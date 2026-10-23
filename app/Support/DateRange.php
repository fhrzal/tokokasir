<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Rentang tanggal untuk filter laporan (hari ini, minggu ini, bulan ini,
 * atau rentang tanggal bebas).
 */
class DateRange
{
    public const PRESETS = [
        'hari_ini' => 'Hari ini',
        'kemarin' => 'Kemarin',
        'minggu_ini' => 'Minggu ini',
        '7_hari' => '7 hari terakhir',
        'bulan_ini' => 'Bulan ini',
        'bulan_lalu' => 'Bulan lalu',
        '30_hari' => '30 hari terakhir',
        'custom' => 'Pilih tanggal',
    ];

    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly string $preset,
    ) {}

    public static function fromRequest(Request $request, string $default = 'bulan_ini'): self
    {
        $preset = $request->query('periode', $default);
        $today = CarbonImmutable::today();

        [$start, $end] = match ($preset) {
            'hari_ini' => [$today, $today],
            'kemarin' => [$today->subDay(), $today->subDay()],
            'minggu_ini' => [$today->startOfWeek(), $today],
            '7_hari' => [$today->subDays(6), $today],
            'bulan_lalu' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            '30_hari' => [$today->subDays(29), $today],
            'custom' => self::parseCustom($request, $today),
            default => [$today->startOfMonth(), $today],
        };

        if (! array_key_exists($preset, self::PRESETS)) {
            $preset = 'bulan_ini';
        }

        return new self($start->startOfDay(), $end->endOfDay(), $preset);
    }

    private static function parseCustom(Request $request, CarbonImmutable $today): array
    {
        try {
            $start = CarbonImmutable::parse($request->query('dari', $today->toDateString()));
            $end = CarbonImmutable::parse($request->query('sampai', $today->toDateString()));
        } catch (\Throwable) {
            return [$today, $today];
        }

        return $start->greaterThan($end) ? [$end, $start] : [$start, $end];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function bounds(): array
    {
        return [$this->start, $this->end];
    }

    public function label(): string
    {
        if ($this->start->isSameDay($this->end)) {
            return $this->start->translatedFormat('d F Y');
        }

        return $this->start->translatedFormat('d M Y').' – '.$this->end->translatedFormat('d M Y');
    }

    /** @return array<string, string> */
    public function query(): array
    {
        return [
            'periode' => $this->preset,
            'dari' => $this->start->toDateString(),
            'sampai' => $this->end->toDateString(),
        ];
    }
}

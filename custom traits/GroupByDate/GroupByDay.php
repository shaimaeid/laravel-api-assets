<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait GroupByDay
{
    /**
     * Group records by each day of the month and apply an aggregate function to a specified column.
     *
     * @param Builder $query
     * @param string $column
     * @param string $aggregate
     * @param string|null $dateColumn
     * @param string|null $from
     * @param string|null $to
     * @return \Illuminate\Support\Collection
     */
    public function scopeGroupByDay(Builder $query, string $column, string $aggregate = 'sum', string $dateColumn = 'created_at', string $from = null, string $to = null)
    {
        if ($from) {
            $query->whereDate($dateColumn, '>=', Carbon::parse($from));
        }
        if ($to) {
            $query->whereDate($dateColumn, '<=', Carbon::parse($to));
        }

        return $query->selectRaw('DATE(' . $dateColumn . ') as date, ' . strtoupper($aggregate) . '(' . $column . ') as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date => $item->total];
            });
    }

    /**
     * Group records by hour for a specific date and apply an aggregate function to a specified column.
     *
     * @param Builder $query
     * @param string $column
     * @param string $date
     * @param string $aggregate
     * @param string|null $dateColumn
     * @return \Illuminate\Support\Collection
     */
    public function scopeGroupByHour(Builder $query, string $column, string $date, string $aggregate = 'sum', string $dateColumn = 'created_at')
    {
        return $query->whereDate($dateColumn, Carbon::parse($date))
            ->selectRaw('HOUR(' . $dateColumn . ') as hour, ' . strtoupper($aggregate) . '(' . $column . ') as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->hour => $item->total];
            });
    }
}
